<?php

namespace App\Jobs;

use App\Services\EmailForwarderService;
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

    public function handle(EmailForwarderService $forwarder): void
    {
        $data = $this->payload['data'] ?? [];

        $forwarder->forward(
            to: $this->forwardTo,
            from: $data['from'] ?? '',
            subject: $data['subject'] ?? '(no subject)',
            html: $data['html'] ?? null,
            text: $data['text'] ?? null,
            attachments: $data['attachments'] ?? [],
        );
    }
}
