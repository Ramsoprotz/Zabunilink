<?php

namespace App\Integrations;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnippeService
{
    protected string $apiKey;
    protected string $webhookSecret;
    protected string $baseUrl = 'https://api.snippe.sh';

    public function __construct()
    {
        // Load from DB settings first, fallback to .env
        $this->apiKey        = Setting::get('snippe_api_key') ?: config('services.snippe.api_key', '');
        $this->webhookSecret = Setting::get('snippe_webhook_secret') ?: config('services.snippe.webhook_secret', '');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function isEnabled(): bool
    {
        return $this->isConfigured() && Setting::get('snippe_enabled', '1') === '1';
    }

    /**
     * Create a mobile-money payment. Snippe fires the USSD push
     * to the customer's phone automatically — no follow-up call needed.
     */
    public function createPayment(array $data, ?string $idempotencyKey = null): array
    {
        return $this->makeRequest('POST', '/v1/payments', $data, array_filter([
            'Idempotency-Key' => $idempotencyKey,
        ]));
    }

    /**
     * Get payment status. Statuses: pending, completed, failed, voided, expired.
     */
    public function getPayment(string $reference): array
    {
        return $this->makeRequest('GET', '/v1/payments/' . $reference);
    }

    /**
     * Verify a webhook signature. Supports both schemes Snippe has shipped:
     * - 2026-01-25 docs: X-Webhook-Signature = HMAC-SHA256("{timestamp}.{raw_body}")
     *   with X-Webhook-Timestamp header
     * - Legacy (WooCommerce plugin): X-Snippe-Signature = HMAC-SHA256(raw_body)
     */
    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        if ($this->webhookSecret === '') {
            return false;
        }

        $headers = array_change_key_case($headers, CASE_LOWER);
        $header = fn (string $key) => is_array($headers[$key] ?? null)
            ? ($headers[$key][0] ?? '')
            : ($headers[$key] ?? '');

        $signature = $header('x-webhook-signature');
        $timestamp = $header('x-webhook-timestamp');

        if ($signature && $timestamp) {
            // Reject stale timestamps (replay protection)
            if (abs(time() - (int) $timestamp) > 300) {
                return false;
            }

            $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret);

            return hash_equals($expected, $signature);
        }

        $legacySignature = $header('x-snippe-signature');

        if ($legacySignature) {
            $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

            return hash_equals($expected, $legacySignature);
        }

        return false;
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $request = Http::withToken($this->apiKey)
                ->withHeaders($headers)
                ->acceptJson()
                ->timeout(30);

            $response = $method === 'POST'
                ? $request->post($url, $data)
                : $request->get($url, $data);

            $body = $response->json() ?? [];

            if ($response->failed()) {
                Log::error('Snippe API error', [
                    'url'      => $url,
                    'status'   => $response->status(),
                    'response' => $body,
                ]);

                return array_merge(['status' => 'error'], is_array($body) ? $body : []);
            }

            return $body;
        } catch (\Throwable $e) {
            Log::error('Snippe API request failed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
