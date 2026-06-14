<?php

namespace App\Jobs;

use App\Services\EmailForwarderService;
use App\Services\InboundEmailFetcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ForwardStaticEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $forwardTo,
        public readonly array $payload,
    ) {
        $this->onQueue('email-routing');
    }

    public function handle(EmailForwarderService $forwarder, InboundEmailFetcherService $fetcher): void
    {
        $data = $this->payload['data'] ?? [];
        $emailId = $data['email_id'] ?? null;

        $html = $data['html'] ?? null;
        $text = $data['text'] ?? null;
        $attachments = $data['attachments'] ?? [];

        if ($emailId && (empty($html) && empty($text))) {
            $inbound = $fetcher->fetch($emailId);
            $html = $inbound['html'];
            $text = $inbound['text'];
            $attachments = $inbound['attachments'];
        }

        $forwarder->forward(
            to: $this->forwardTo,
            from: $data['from'] ?? '',
            subject: $data['subject'] ?? '(no subject)',
            html: $html,
            text: $text,
            attachments: $attachments,
        );
    }
}
