<?php

namespace App\Services;

use Resend;

class InboundEmailFetcherService
{
    public function fetch(string $emailId): array
    {
        $client = Resend::client(env('RESEND_API_KEY'));

        $email = $client->emails->receiving->get($emailId);

        $attachments = [];
        $list = $client->emails->receiving->attachments->list($emailId);

        foreach ($list as $a) {
            $attachment = is_array($a) ? $a : $a->toArray();

            if (empty($attachment['download_url'])) {
                continue;
            }

            $mapped = [
                'filename' => $attachment['filename'] ?? 'attachment',
                'path' => $attachment['download_url'],
            ];

            if (! empty($attachment['content_id'])) {
                $mapped['content_id'] = trim($attachment['content_id'], '<>');
            }

            $attachments[] = $mapped;
        }

        return [
            'html' => $email->html,
            'text' => $email->text,
            'attachments' => $attachments,
        ];
    }
}
