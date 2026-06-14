<?php

namespace App\Jobs;

use App\Models\EmailRoutingLog;
use App\Models\Site;
use App\Services\EmailForwarderService;
use App\Services\UserEmailResolverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RouteInboundEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $logId,
        public readonly array $payload,
    ) {
        $this->onQueue('email-routing');
    }

    public function handle(UserEmailResolverService $resolver, EmailForwarderService $forwarder): void
    {
        $log = EmailRoutingLog::findOrFail($this->logId);
        $log->update(['status' => 'resolving']);

        $site = Site::where('site_id', $log->site_id)->first();

        if (! $site || ! $site->active) {
            $log->update([
                'status' => 'failed',
                'failure_reason' => $site ? 'Site is inactive' : "Site {$log->site_id} not found",
            ]);
            $this->fail("Site {$log->site_id} not found or inactive");
            return;
        }

        $data = $this->payload['data'] ?? [];

        $resolvedEmail = $resolver->resolve($site, $log->user_id);
        $log->update(['resolved_email' => $resolvedEmail]);

        $resendId = $forwarder->forward(
            to: $resolvedEmail,
            from: $data['from'] ?? '',
            subject: $data['subject'] ?? '(no subject)',
            html: $data['html'] ?? null,
            text: $data['text'] ?? null,
            attachments: $data['attachments'] ?? [],
        );

        $log->update([
            'status' => 'forwarded',
            'forwarded_at' => now(),
            'resend_email_id' => $resendId,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        EmailRoutingLog::where('id', $this->logId)->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
