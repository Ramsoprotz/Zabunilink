<?php

namespace App\Integrations;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SelcomService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $vendor;
    protected string $baseUrl;

    public function __construct()
    {
        // Load from DB settings first, fallback to .env
        $this->apiKey    = Setting::get('selcom_api_key') ?: config('services.selcom.api_key', '');
        $this->apiSecret = Setting::get('selcom_api_secret') ?: config('services.selcom.api_secret', '');
        $this->vendor    = Setting::get('selcom_vendor') ?: config('services.selcom.vendor', '');

        $env = Setting::get('selcom_environment', 'sandbox');
        $this->baseUrl = $env === 'live'
            ? 'https://apigw.selcommobile.com/v1'
            : 'https://sandbox.selcommobile.com/v1';
    }

    /**
     * Create a payment order (returns payment gateway URL for redirect).
     */
    public function createOrder(array $data): array
    {
        $url = $this->baseUrl . '/checkout/create-order';
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Check the status of an order.
     */
    public function checkOrderStatus(string $orderId): array
    {
        $url = $this->baseUrl . '/checkout/order-status';
        return $this->makeRequest('GET', $url, ['order_id' => $orderId]);
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $orderId): array
    {
        $url = $this->baseUrl . '/checkout/cancel-order';
        return $this->makeRequest('POST', $url, ['order_id' => $orderId]);
    }

    /**
     * Verify a callback signature from Selcom.
     */
    public function verifyCallbackSignature(string $payload, array $headers): bool
    {
        $receivedDigest = $headers['Digest'] ?? $headers['DIGEST'] ?? $headers['digest'] ?? '';
        $timestamp      = $headers['Timestamp'] ?? $headers['TIMESTAMP'] ?? $headers['timestamp'] ?? '';

        if (! $receivedDigest || ! $timestamp) {
            return false;
        }

        $signData = "timestamp={$timestamp}&{$payload}";
        $expectedDigest = base64_encode(hash_hmac('sha256', $signData, $this->apiSecret, true));

        return hash_equals($expectedDigest, $receivedDigest);
    }

    /**
     * Get the vendor/till number.
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Check if Selcom is properly configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret) && ! empty($this->vendor);
    }

    // ── Internal ────────────────────────────────────────────────

    /**
     * Make a signed request to the Selcom API.
     * Signature follows Selcom spec: timestamp=X&key1=val1&key2=val2
     */
    protected function makeRequest(string $method, string $url, array $data): array
    {
        $timestamp     = date('Y-m-d\TH:i:sP');
        $authorization = base64_encode($this->apiKey);

        // Build signature exactly as Selcom expects
        $signedFields = implode(',', array_keys($data));
        $digest       = $this->computeSignature($data, $signedFields, $timestamp);

        $headers = [
            'Authorization'  => "SELCOM {$authorization}",
            'Digest-Method'  => 'HS256',
            'Digest'         => $digest,
            'Timestamp'      => $timestamp,
            'Signed-Fields'  => $signedFields,
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
            'Cache-Control'  => 'no-cache',
        ];

        try {
            Log::info('Selcom API request', [
                'method' => $method,
                'url'    => $url,
                'data'   => $this->redactSensitive($data),
            ]);

            $response = match (strtoupper($method)) {
                'POST' => Http::withHeaders($headers)->timeout(90)->post($url, $data),
                'GET'  => Http::withHeaders($headers)->timeout(90)->get($url, $data),
                default => Http::withHeaders($headers)->timeout(90)->post($url, $data),
            };

            $body = $response->json() ?? [];

            Log::info('Selcom API response', [
                'url'    => $url,
                'status' => $response->status(),
                'result' => $body['result'] ?? 'unknown',
            ]);

            if ($response->successful()) {
                return $body;
            }

            Log::error('Selcom API request failed', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'result'  => 'FAIL',
                'message' => $body['message'] ?? 'HTTP ' . $response->status(),
                'data'    => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Selcom API exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'result'  => 'FAIL',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compute HMAC-SHA256 signature exactly as Selcom expects.
     * Format: timestamp=TIMESTAMP&key1=val1&key2=val2...
     */
    protected function computeSignature(array $parameters, string $signedFields, string $timestamp): string
    {
        $fieldsOrder = explode(',', $signedFields);
        $signData = "timestamp={$timestamp}";

        foreach ($fieldsOrder as $key) {
            $value = $parameters[$key] ?? '';
            $signData .= "&{$key}={$value}";
        }

        return base64_encode(hash_hmac('sha256', $signData, $this->apiSecret, true));
    }

    /**
     * Normalize phone number to 255XXXXXXXXX format (Tanzania).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading + if present
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // Add country code if missing
        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        if (! str_starts_with($phone, '255')) {
            $phone = '255' . $phone;
        }

        return $phone;
    }

    /**
     * Redact sensitive fields for logging.
     */
    protected function redactSensitive(array $data): array
    {
        $redact = ['buyer_phone', 'payment_phone'];
        foreach ($redact as $key) {
            if (isset($data[$key])) {
                $data[$key] = substr($data[$key], 0, 6) . '***';
            }
        }
        return $data;
    }
}
