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
        foreach ($email->attachments ?? [] as $a) {
            $attachment = is_array($a) ? $a : $a->toArray();
            if (! empty($attachment['download_url'])) {
                $attachments[] = [
                    'filename' => $attachment['filename'] ?? 'attachment',
                    'path' => $attachment['download_url'],
                ];
            }
        }

        return [
            'html' => $email->html,
            'text' => $email->text,
            'attachments' => $attachments,
        ];
    }
}
