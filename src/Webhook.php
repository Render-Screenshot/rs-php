<?php

declare(strict_types=1);

namespace RenderScreenshot;

use DateTime;
use DateTimeInterface;

/**
 * Webhook verification and parsing for the RenderScreenshot SDK.
 *
 * @example
 * // In your webhook handler
 * $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
 * $timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';
 * $payload = file_get_contents('php://input');
 *
 * if (!Webhook::verify($payload, $signature, $timestamp, $webhookSecret)) {
 *     http_response_code(401);
 *     exit('Invalid signature');
 * }
 *
 * $event = Webhook::parse($payload);
 * if ($event['type'] === 'screenshot.completed') {
 *     echo "Screenshot ready: " . $event['data']['response']['url'];
 * }
 */
class Webhook
{
    /**
     * Verify a webhook signature.
     *
     * Webhooks are signed using HMAC-SHA256 with the format:
     * `sha256=hmac(secret, "{timestamp}.{payload}")`
     *
     * @param string $payload   The raw request body as a string
     * @param string $signature The X-Webhook-Signature header value
     * @param string $timestamp The X-Webhook-Timestamp header value
     * @param string $secret    Your webhook signing secret from the dashboard
     * @param int    $tolerance Maximum age of webhook in seconds (default: 300 = 5 minutes)
     *
     * @return bool True if the signature is valid, false otherwise
     *
     * @example
     * // In your webhook handler (Symfony/Laravel example)
     * $signature = $request->headers->get('X-Webhook-Signature', '');
     * $timestamp = $request->headers->get('X-Webhook-Timestamp', '');
     * $payload = $request->getContent();
     *
     * if (!Webhook::verify($payload, $signature, $timestamp, WEBHOOK_SECRET)) {
     *     return new Response('Invalid signature', 401);
     * }
     */
    public static function verify(
        string $payload,
        string $signature,
        string $timestamp,
        string $secret,
        int $tolerance = 300
    ): bool {
        // Validate inputs
        if (empty($payload) || empty($signature) || empty($timestamp) || empty($secret)) {
            return false;
        }

        // Check timestamp to prevent replay attacks
        if (!ctype_digit($timestamp)) {
            return false;
        }

        $timestampNum = (int) $timestamp;
        $now = time();

        if (abs($now - $timestampNum) > $tolerance) {
            return false;
        }

        // Compute expected signature
        $message = "{$timestamp}.{$payload}";
        $expected = 'sha256=' . hash_hmac('sha256', $message, $secret);

        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($expected, $signature);
    }

    /**
     * Parse a webhook payload into a typed event array.
     *
     * @param string|array<string, mixed> $payload The raw request body (string or parsed array)
     *
     * @return array{
     *     id: string,
     *     type: string,
     *     timestamp: DateTimeInterface,
     *     data: array<string, mixed>
     * } Typed webhook event
     *
     * @example
     * $event = Webhook::parse($request->getContent());
     * if ($event['type'] === 'screenshot.completed') {
     *     $response = $event['data']['response'];
     *     echo "Screenshot ready: " . $response['url'];
     * }
     */
    public static function parse(string|array $payload): array
    {
        if (is_string($payload)) {
            $raw = json_decode($payload, true);
            if (!is_array($raw)) {
                $raw = [];
            }
        } else {
            $raw = $payload;
        }

        // Parse timestamp
        $timestampStr = $raw['timestamp'] ?? '';
        try {
            $timestamp = new DateTime(str_replace('Z', '+00:00', $timestampStr));
        } catch (\Exception) {
            $timestamp = new DateTime();
        }

        $eventType = $raw['event'] ?? 'screenshot.completed';
        $eventData = [];

        // Parse based on event type
        $data = $raw['data'] ?? [];

        if ($eventType === 'screenshot.completed') {
            $eventData['response'] = [
                'url' => $data['screenshot_url'] ?? '',
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'format' => $data['format'] ?? 'png',
                'size' => $data['size'] ?? 0,
                'cached' => $data['cached'] ?? false,
            ];
            if (isset($data['url'])) {
                $eventData['url'] = $data['url'];
            }
        } elseif ($eventType === 'screenshot.failed') {
            $eventData['error'] = [
                'code' => 'render_failed',
                'message' => $data['error'] ?? 'Unknown error',
            ];
            if (isset($data['url'])) {
                $eventData['url'] = $data['url'];
            }
        } elseif (in_array($eventType, ['batch.completed', 'batch.failed'], true)) {
            $eventData['batch_id'] = $raw['id'] ?? '';
        }

        return [
            'id' => $raw['id'] ?? '',
            'type' => $eventType,
            'timestamp' => $timestamp,
            'data' => $eventData,
        ];
    }

    /**
     * Extract webhook headers from a request-like object.
     *
     * This helper works with various HTTP frameworks (Laravel, Symfony, plain PHP)
     * to extract the signature and timestamp headers.
     *
     * @param array<string, string|mixed> $headers Headers array or dictionary
     *
     * @return array{string, string} Tuple of [signature, timestamp]
     *
     * @example
     * // Plain PHP
     * [$signature, $timestamp] = Webhook::extractHeaders($_SERVER);
     *
     * // Laravel
     * [$signature, $timestamp] = Webhook::extractHeaders($request->headers->all());
     *
     * // Symfony
     * [$signature, $timestamp] = Webhook::extractHeaders($request->headers->all());
     */
    public static function extractHeaders(array $headers): array
    {
        $getHeader = function (string $name) use ($headers): string {
            // Try exact match
            if (isset($headers[$name]) && is_string($headers[$name])) {
                return $headers[$name];
            }

            // Try lowercase
            $lower = strtolower($name);
            if (isset($headers[$lower]) && is_string($headers[$lower])) {
                return $headers[$lower];
            }

            // Try PHP-style (HTTP_X_WEBHOOK_SIGNATURE)
            $phpName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            if (isset($headers[$phpName]) && is_string($headers[$phpName])) {
                return $headers[$phpName];
            }

            // Handle array values (from some frameworks)
            if (isset($headers[$lower]) && is_array($headers[$lower])) {
                $value = $headers[$lower][0] ?? '';

                return is_string($value) ? $value : '';
            }

            return '';
        };

        $signature = $getHeader('X-Webhook-Signature');
        $timestamp = $getHeader('X-Webhook-Timestamp');

        return [$signature, $timestamp];
    }
}
