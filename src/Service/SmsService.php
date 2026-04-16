<?php

namespace App\Service;

class SmsService
{
    private const DEFAULT_ACCOUNT_SID = 'ACd6a636fad3cc49b59bcbbc225d9713a3';
    private const DEFAULT_AUTH_TOKEN = 'dbcd6327269e4de6fca3985f0536998b';
    private const DEFAULT_FROM_NUMBER = '+17755005336';

    public function send(?string $to, string $message): bool
    {
        $to = $this->normalizePhone($to);

        if ($to === null) {
            return false;
        }

        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? $_SERVER['TWILIO_ACCOUNT_SID'] ?? self::DEFAULT_ACCOUNT_SID;
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? $_SERVER['TWILIO_AUTH_TOKEN'] ?? self::DEFAULT_AUTH_TOKEN;
        $from = $_ENV['TWILIO_FROM_NUMBER'] ?? $_SERVER['TWILIO_FROM_NUMBER'] ?? self::DEFAULT_FROM_NUMBER;

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $accountSid);
        $body = http_build_query([
            'From' => $from,
            'To' => $to,
            'Body' => $message,
        ]);

        if (function_exists('curl_init')) {
            return $this->sendWithCurl($url, $accountSid, $authToken, $body);
        }

        return $this->sendWithStream($url, $accountSid, $authToken, $body);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 8) {
            return '+216' . $digits;
        }

        return '+' . $digits;
    }

    private function sendWithCurl(string $url, string $accountSid, string $authToken, string $body): bool
    {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $accountSid . ':' . $authToken,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $statusCode >= 200 && $statusCode < 300;
    }

    private function sendWithStream(string $url, string $accountSid, string $authToken, string $body): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Basic ' . base64_encode($accountSid . ':' . $authToken),
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($body),
                ],
                'content' => $body,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return $result !== false;
    }
}
