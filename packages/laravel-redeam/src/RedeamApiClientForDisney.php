<?php

namespace CodeCreatives\LaravelRedeam;

use Illuminate\Support\Facades\Http;

class RedeamApiClientForDisney
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function get(string $uri, array $payload = []): array
    {
        return Http::asForm()
            ->timeout('600')
            ->withHeaders($this->getHeaders())
            ->get($this->getUrl($uri), $payload)
            ->json();
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function getHeaders(array $headers = []): array
    {
        return array_merge(
            [
                'X-API-Key' => config('redeam.disney.api_key'),
                'X-API-Secret' => config('redeam.disney.api_secret'),
            ],
            $headers
        );
    }

    public function getUrl(string $uri): string
    {
        $domain = $this->getDomain();
        $version = $this->getVersion();

        return "$domain/$version/$uri";
    }

    public function getDomain(): string
    {
        $host = $this->getHost();

        return "https://$host";
    }

    public function getHost(): string
    {
        return config('redeam.disney.host');
    }

    public function getVersion(): string
    {
        return config('redeam.disney.version');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $uri, array $payload = []): array
    {
        return Http::asJson()
            ->timeout('600')
            ->withHeaders($this->getHeaders())
            ->post($this->getUrl($uri), $payload)
            ->json();
    }

    public function delete(string $uri, array $payload = [])
    {
        return Http::timeout('600')
            ->withHeaders($this->getHeaders())
            ->delete($this->getUrl($uri), $payload)
            ->json();
    }

    /**
     * @throws \Exception
     */
    public function put(string $uri)
    {
        return Http::timeout('600')
            ->withHeaders($this->getHeaders())
            ->send('PUT', $this->getUrl($uri));
    }
}
