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
        $fromDomain = env('RESEND_FROM_DOMAIN', 'resumetics.com');
        $fromAddress = $this->buildFromAddress($from, $fromDomain);

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
                'content_id' => $a['content_id'] ?? null,
            ]), $attachments);
        }

        $client = Resend::client(env('RESEND_API_KEY'));
        $response = $client->emails->send($params);

        return $response->id ?? '';
    }

    private function buildFromAddress(string $originalFrom, string $domain): string
    {
        $noreply = "noreply@{$domain}";

        if (preg_match('/^(.+?)\s*<[^>]+>$/', trim($originalFrom), $matches)) {
            $name = trim($matches[1], ' "\'');
            if ($name !== '') {
                return "\"{$name} via Resumetics\" <{$noreply}>";
            }
        }

        return $noreply;
    }
}
