<?php

namespace App\Service;

class SmsService
{
    public function send(?string $to, string $message): bool
    {
        $to = $this->normalizePhone($to);

        if ($to === null) {
            $this->logError('SMS Error: Invalid phone number provided');
            return false;
        }

        $accountSid = $_ENV['TWILIO_SID'] ?? $_SERVER['TWILIO_SID'] ?? null;
        $authToken = $_ENV['TWILIO_TOKEN'] ?? $_SERVER['TWILIO_TOKEN'] ?? null;
        $from = $_ENV['TWILIO_FROM'] ?? $_SERVER['TWILIO_FROM'] ?? null;

        if (!$accountSid || !$authToken || !$from) {
            $this->logError(sprintf('SMS Error: Twilio credentials not configured (SID=%s, TOKEN=%s, FROM=%s)', $accountSid ? 'SET' : 'MISSING', $authToken ? 'SET' : 'MISSING', $from ? 'SET' : 'MISSING'));
            return false;
        }

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
            // Validate that it's a valid E.164 format
            if (preg_match('/^\+\d{1,15}$/', $phone)) {
                return $phone;
            }
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // Handle 8-digit Tunisian numbers (add +216)
        if (strlen($digits) === 8) {
            return '+216' . $digits;
        }

        // Handle numbers that already have country code but no +
        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        // Invalid number
        return null;
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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!($statusCode >= 200 && $statusCode < 300)) {
            $this->logError(sprintf('SMS send failed via cURL: HTTP %d - %s', $statusCode, $error ?: $response));
            return false;
        }

        return true;
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
                'ignore_errors' => false,
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        try {
            $result = file_get_contents($url, false, $context);
            if ($result === false) {
                $error = error_get_last();
                $this->logError(sprintf('SMS send failed via stream: %s', $error['message'] ?? 'Unknown error'));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->logError(sprintf('SMS send exception via stream: %s', $e->getMessage()));
            return false;
        }
    }

    private function logError(string $message): void
    {
        $logDir = sys_get_temp_dir() . '/echoCare_logs';
        
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        
        $logFile = $logDir . '/sms_errors.log';
        $logEntry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
