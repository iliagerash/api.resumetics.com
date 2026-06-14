<?php

namespace App\Services;

use Resend;
use RuntimeException;

class EmailForwarderService
{
    public function forward(
        string $to,
        string $from,
        string $subject,
        ?string $html,
        ?string $text,
        array $attachments = []
    ): string {
        $fromAddress = 'noreply@' . env('RESEND_FROM_DOMAIN', 'resumetics.com');

        $params = [
            'from' => $fromAddress,
            'to' => [$to],
            'reply_to' => $from,
            'subject' => $subject,
        ];

        if ($html) {
            $params['html'] = $html;
        }

        $params['text'] = $text ?: ($html ? strip_tags($html) : ' ');

        if (! empty($attachments)) {
            \Illuminate\Support\Facades\Log::debug('Inbound attachments', $attachments);

            $mapped = [];
            foreach ($attachments as $a) {
                $content = $a['content'] ?? $a['data'] ?? $a['body'] ?? null;
                if (empty($content)) {
                    continue;
                }
                $mapped[] = [
                    'filename' => $a['filename'] ?? $a['name'] ?? 'attachment',
                    'content' => $content,
                ];
            }

            if (! empty($mapped)) {
                $params['attachments'] = $mapped;
            }
        }

        $client = Resend::client(env('RESEND_API_KEY'));
        $response = $client->emails->send($params);

        \Illuminate\Support\Facades\Log::debug('Resend send response', $response->toArray());

        return $response->id ?? '';
    }
}
