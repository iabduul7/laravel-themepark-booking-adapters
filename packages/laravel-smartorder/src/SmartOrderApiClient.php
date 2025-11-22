<?php

namespace CodeCreatives\LaravelSmartOrder;

use App\Models\UniversalSmartOrderAuthToken;
use Illuminate\Support\Facades\Http;

class SmartOrderApiClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function get(string $uri, array $payload = []): array
    {
        $payload['customerId'] = config('smartorder.customer_id');

        return Http::timeout('600')
            ->withHeaders($this->getHeaders())
            ->get($this->getUrl($uri), $payload)
            ->json();
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    protected function getHeaders(array $headers = []): array
    {
        return array_merge(
            [
                'Authorization' => "Bearer {$this->getToken()}",
            ],
            $headers
        );
    }

    private function getToken(): string
    {
        $token = UniversalSmartOrderAuthToken::query()
            ->latest()
            ->first();
        if (filled($token) && $token->valid()) {
            return $token->token;
        }

        return $this->refreshToken();
    }

    private function refreshToken(): string
    {
        $response = Http::asForm()
            ->timeout('600')
            ->post($this->getUrl('connect/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('smartorder.client_username'),
                'client_secret' => config('smartorder.client_secret'),
                'scope' => 'SmartOrder',
            ]);
        $token = $response->json('access_token');
        $expiresAt = $response->json('expires_in');
        $expiresAt = now()
            ->addSeconds($expiresAt)
            ->format('Y-m-d H:i:s');
        UniversalSmartOrderAuthToken::query()
            ->create([
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $uri, array $payload = []): ?array
    {
        $payload['customerId'] = config('smartorder.customer_id');

        return Http::asJson()
            ->timeout('600')
            ->withHeaders($this->getHeaders())
            ->post($this->getUrl($uri), $payload)
            ->json();
    }

    private function getUrl(string $uri): string
    {
        return "{$this->getDomain()}/$uri";
    }

    private function getDomain(): string
    {
        return "https://{$this->getHost()}";
    }

    private function getHost(): string
    {
        return config('smartorder.host');
    }
}
