<?php

namespace App\Services;

use Resend;

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
            $params['attachments'] = array_map(fn ($a) => array_filter([
                'filename' => $a['filename'] ?? 'attachment',
                'content' => $a['content'] ?? null,
                'path' => $a['path'] ?? null,
            ]), $attachments);
        }

        $client = Resend::client(env('RESEND_API_KEY'));
        $response = $client->emails->send($params);

        return $response->id ?? '';
    }
}
