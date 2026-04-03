<?php

namespace App\Integrations;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NextSmsService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $senderId;
    protected bool $testMode;

    public function __construct()
    {
        // DB settings take priority over .env values
        $this->username  = Setting::get('nextsms_username', config('services.nextsms.username', ''));
        $this->password  = Setting::get('nextsms_password', config('services.nextsms.password', ''));
        $this->senderId  = Setting::get('nextsms_sender_id', config('services.nextsms.sender_id', 'ZABUNILINK'));
        $this->testMode  = (bool) Setting::get('nextsms_test_mode', app()->environment() !== 'production');

        $this->baseUrl = $this->testMode
            ? 'https://messaging-service.co.tz/api/sms/v1/test'
            : 'https://messaging-service.co.tz/api/sms/v1';
    }

    /**
     * Normalise a phone number to Tanzania E.164 format (255xxxxxxxxx).
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Convert 07xxxxxxxx → 2557xxxxxxxx
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '255' . substr($digits, 1);
        }

        // Convert +255... → 255...
        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            return $digits;
        }

        // Return as-is if already 12 digits starting with 255
        return $digits;
    }

    /**
     * Build HTTP client with Basic auth headers.
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        $credentials = base64_encode("{$this->username}:{$this->password}");

        return Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);
    }

    /**
     * Send a single SMS to one recipient.
     */
    public function sendSms(string $phone, string $message, ?string $reference = null): array
    {
        if (empty($this->username) || empty($this->password)) {
            Log::warning('NextSMS credentials not configured — SMS skipped.', compact('phone'));
            return ['status' => 'skipped', 'reason' => 'credentials_missing'];
        }

        $phone = $this->normalizePhone($phone);

        try {
            $payload = [
                'from'      => $this->senderId,
                'to'        => $phone,
                'text'      => $message,
                'reference' => $reference ?? uniqid('zl_'),
            ];

            $response = $this->client()->post($this->baseUrl . '/text/single', $payload);

            if ($response->successful()) {
                Log::info('NextSMS sent', ['phone' => $phone, 'test' => $this->testMode]);
                return $response->json() ?? ['status' => 'sent'];
            }

            Log::error('NextSMS send failed', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['status' => 'failed', 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('NextSMS exception', ['phone' => $phone, 'message' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send the same message to multiple recipients (bulk).
     * Uses the /text/single endpoint with an array of phone numbers.
     */
    public function sendBulk(array $phones, string $message, ?string $reference = null): array
    {
        if (empty($this->username) || empty($this->password)) {
            Log::warning('NextSMS credentials not configured — bulk SMS skipped.');
            return ['status' => 'skipped', 'reason' => 'credentials_missing'];
        }

        $phones = array_map(fn($p) => $this->normalizePhone($p), $phones);

        try {
            $payload = [
                'from'      => $this->senderId,
                'to'        => $phones,
                'text'      => $message,
                'reference' => $reference ?? uniqid('zl_bulk_'),
            ];

            $response = $this->client()->post($this->baseUrl . '/text/single', $payload);

            if ($response->successful()) {
                Log::info('NextSMS bulk sent', ['count' => count($phones), 'test' => $this->testMode]);
                return $response->json() ?? ['status' => 'sent'];
            }

            Log::error('NextSMS bulk send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['status' => 'failed', 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('NextSMS bulk exception', ['message' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send different messages to different recipients.
     * Each item in $messages: ['to' => '255xxxxxxxxx', 'text' => '...']
     */
    public function sendMulti(array $messages, ?string $reference = null): array
    {
        if (empty($this->username) || empty($this->password)) {
            Log::warning('NextSMS credentials not configured — multi SMS skipped.');
            return ['status' => 'skipped', 'reason' => 'credentials_missing'];
        }

        $normalised = array_map(function ($msg) {
            return [
                'from' => $msg['from'] ?? $this->senderId,
                'to'   => $this->normalizePhone($msg['to']),
                'text' => $msg['text'],
            ];
        }, $messages);

        try {
            $payload = [
                'messages'  => $normalised,
                'reference' => $reference ?? uniqid('zl_multi_'),
            ];

            $response = $this->client()->post($this->baseUrl . '/text/multi', $payload);

            if ($response->successful()) {
                Log::info('NextSMS multi sent', ['count' => count($normalised), 'test' => $this->testMode]);
                return $response->json() ?? ['status' => 'sent'];
            }

            Log::error('NextSMS multi send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['status' => 'failed', 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('NextSMS multi exception', ['message' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch delivery reports from NextSMS.
     */
    public function getReports(): array
    {
        if (empty($this->username) || empty($this->password)) {
            return ['status' => 'skipped', 'reason' => 'credentials_missing'];
        }

        try {
            $response = $this->client()->get('https://messaging-service.co.tz/api/sms/v1/reports');

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('NextSMS reports exception', ['message' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}
