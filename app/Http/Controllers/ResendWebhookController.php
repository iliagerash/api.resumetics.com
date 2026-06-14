<?php

namespace App\Http\Controllers;

use App\Jobs\ForwardStaticEmailJob;
use App\Jobs\RouteInboundEmailJob;
use App\Models\EmailRoutingLog;
use App\Models\StaticRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->json()->all();

        if (($payload['type'] ?? '') !== 'email.received') {
            return response()->json(['ok' => true]);
        }

        $data = $payload['data'] ?? [];
        $recipients = $data['to'] ?? [];
        $recipient = is_array($recipients) ? ($recipients[0] ?? '') : $recipients;

        $localPart = strtolower(explode('@', $recipient)[0] ?? '');

        if (preg_match('/^s(\d+)u(\d+)$/', $localPart, $matches)) {
            $log = EmailRoutingLog::create([
                'resend_email_id' => $data['email_id'] ?? null,
                'recipient' => $recipient,
                'site_id' => (int) $matches[1],
                'user_id' => (int) $matches[2],
                'status' => 'received',
                'received_at' => now(),
            ]);

            RouteInboundEmailJob::dispatch($log->id, $payload);

            return response()->json(['ok' => true]);
        }

        $staticRoute = StaticRoute::where('recipient', $recipient)->first();

        if ($staticRoute) {
            ForwardStaticEmailJob::dispatch($staticRoute->forward_to, $payload);
        }

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = env('RESEND_WEBHOOK_SECRET');

        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('svix-signature');

        if (empty($signature)) {
            return false;
        }

        $msgId = $request->header('svix-id', '');
        $msgTimestamp = $request->header('svix-timestamp', '');
        $rawBody = $request->getContent();

        $toSign = "{$msgId}.{$msgTimestamp}.{$rawBody}";

        $secretBytes = base64_decode(str_replace('whsec_', '', $secret));
        $computedHmac = base64_encode(hash_hmac('sha256', $toSign, $secretBytes, true));

        foreach (explode(' ', $signature) as $part) {
            $versionedSig = explode(',', $part, 2);
            if (count($versionedSig) === 2 && hash_equals($computedHmac, $versionedSig[1])) {
                return true;
            }
        }

        return false;
    }
}
