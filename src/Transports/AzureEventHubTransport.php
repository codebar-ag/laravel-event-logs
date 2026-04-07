<?php

namespace CodebarAg\LaravelEventLogs\Transports;

use CodebarAg\LaravelEventLogs\Contracts\EventLogTransport;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AzureEventHubTransport implements EventLogTransport
{
    private string $endpoint;

    private string $hub;

    private string $policy;

    private string $primaryKey;

    private ?string $cachedToken = null;

    private ?int $cachedTokenExpiresAt = null;

    public function __construct()
    {
        $providerConfig = (array) config('laravel-event-logs.providers.azure_event_hub', []);
        $endpointConfig = $providerConfig['endpoint'] ?? null;
        $hubConfig = $providerConfig['path'] ?? null;
        $policyConfig = $providerConfig['policy_name'] ?? null;
        $keyConfig = $providerConfig['primary_key'] ?? null;

        $this->endpoint = is_string($endpointConfig) ? rtrim($endpointConfig, '/') : '';
        $this->hub = is_string($hubConfig) ? trim($hubConfig, '/') : '';
        $this->policy = is_string($policyConfig) ? $policyConfig : '';
        $this->primaryKey = is_string($keyConfig) ? $keyConfig : '';
    }

    private static function resource(string $endpoint, string $hub): string
    {
        $parts = parse_url($endpoint);
        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower($parts['host'] ?? '');
        $hub = trim($hub, '/');

        return "{$scheme}://{$host}/{$hub}";
    }

    public function resourceUrl(): string
    {
        return self::resource($this->endpoint, $this->hub);
    }

    public function buildToken(): string
    {
        $cacheEnabled = (bool) config('laravel-event-logs.providers.azure_event_hub.cache_sas_token', true);
        $bufferRaw = config('laravel-event-logs.providers.azure_event_hub.token_cache_buffer_seconds', 60);
        $buffer = is_int($bufferRaw) ? $bufferRaw : 60;
        $ttlRaw = config('laravel-event-logs.providers.azure_event_hub.sas_ttl_seconds', 7200);
        $ttl = is_int($ttlRaw) ? $ttlRaw : 7200;
        $now = time();

        if (
            $cacheEnabled
            && $this->cachedToken !== null
            && $this->cachedTokenExpiresAt !== null
            && $now < ($this->cachedTokenExpiresAt - max(0, $buffer))
        ) {
            return $this->cachedToken;
        }

        $resource = $this->resourceUrl();
        $encodedResource = rawurlencode($resource);
        $expiry = $now + max(60, $ttl);
        $stringToSign = $encodedResource."\n".$expiry;
        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $stringToSign, $this->primaryKey, true)));

        $token = "SharedAccessSignature sr={$encodedResource}&sig={$signature}&se={$expiry}&skn={$this->policy}";

        if ($cacheEnabled) {
            $this->cachedToken = $token;
            $this->cachedTokenExpiresAt = $expiry;
        }

        return $token;
    }

    public function send(EventLog $eventLog): Response
    {
        $resource = $this->resourceUrl();
        $postUrl = "{$resource}/messages?api-version=2014-01";

        $response = Http::retry(3, 500)
            ->withHeaders([
                'Authorization' => $this->buildToken(),
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode($eventLog->toProviderPayload(), JSON_UNESCAPED_SLASHES) ?: '{}', 'application/json')
            ->post($postUrl);

        return $response;
    }
}
