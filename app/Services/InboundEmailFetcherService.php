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

        $html = $email->html;

        // Gmail embeds inline images as data: URIs in HTML rather than cid: references.
        // Replace them in document order with cid: references pointing to the inline attachments
        // (those that have a content_id), which Resend makes available via download_url.
        if ($html) {
            $inlineAttachments = array_values(array_filter($attachments, fn ($a) => isset($a['content_id'])));

            if (! empty($inlineAttachments)) {
                $index = 0;
                $html = preg_replace_callback(
                    '/src="data:[^"]+"/i',
                    function () use ($inlineAttachments, &$index) {
                        if ($index < count($inlineAttachments)) {
                            return 'src="cid:' . $inlineAttachments[$index++]['content_id'] . '"';
                        }
                        return 'src=""';
                    },
                    $html
                );
            }
        }

        return [
            'html' => $html,
            'text' => $email->text,
            'attachments' => $attachments,
        ];
    }
}
