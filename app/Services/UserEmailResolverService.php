<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UserEmailResolverService
{
    public function resolve(Site $site, int $userId): string
    {
        $response = Http::withToken($site->api_key)
            ->timeout(10)
            ->get("{$site->api_url}/users/{$userId}/email");

        if (! $response->successful()) {
            throw new RuntimeException(
                "User lookup failed for site {$site->site_id}, user {$userId}: HTTP {$response->status()}"
            );
        }

        $responsePath = $site->response_path ?? 'email';
        $email = data_get($response->json(), $responsePath);

        if (empty($email)) {
            throw new RuntimeException(
                "No email found in response for site {$site->site_id}, user {$userId}"
            );
        }

        return $email;
    }
}
