<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use RenderScreenshot\Webhook;

class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_key';

    public function testVerifyValidSignature(): void
    {
        $payload = '{"event":"screenshot.completed","data":{"url":"https://example.com"}}';
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        $this->assertTrue(Webhook::verify($payload, $signature, $timestamp, self::SECRET));
    }

    public function testVerifyInvalidSignature(): void
    {
        $payload = '{"event":"screenshot.completed"}';
        $timestamp = (string) time();
        $signature = 'sha256=invalid_signature';

        $this->assertFalse(Webhook::verify($payload, $signature, $timestamp, self::SECRET));
    }

    public function testVerifyExpiredTimestamp(): void
    {
        $payload = '{"event":"screenshot.completed"}';
        $timestamp = (string) (time() - 600); // 10 minutes ago
        $signature = 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        $this->assertFalse(Webhook::verify($payload, $signature, $timestamp, self::SECRET));
    }

    public function testVerifyCustomTolerance(): void
    {
        $payload = '{"event":"screenshot.completed"}';
        $timestamp = (string) (time() - 400); // 6+ minutes ago
        $signature = 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        // Should fail with default tolerance (300s)
        $this->assertFalse(Webhook::verify($payload, $signature, $timestamp, self::SECRET, 300));

        // Should pass with higher tolerance (600s)
        $this->assertTrue(Webhook::verify($payload, $signature, $timestamp, self::SECRET, 600));
    }

    public function testVerifyEmptyInputs(): void
    {
        $this->assertFalse(Webhook::verify('', 'sig', '123', self::SECRET));
        $this->assertFalse(Webhook::verify('payload', '', '123', self::SECRET));
        $this->assertFalse(Webhook::verify('payload', 'sig', '', self::SECRET));
        $this->assertFalse(Webhook::verify('payload', 'sig', '123', ''));
    }

    public function testVerifyInvalidTimestampFormat(): void
    {
        $this->assertFalse(Webhook::verify('payload', 'sig', 'not-a-number', self::SECRET));
    }

    public function testParseScreenshotCompleted(): void
    {
        $payload = [
            'id' => 'evt_123',
            'event' => 'screenshot.completed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'url' => 'https://example.com',
                'screenshot_url' => 'https://cdn.example.com/image.png',
                'width' => 1200,
                'height' => 630,
                'format' => 'png',
                'size' => 12345,
                'cached' => true,
            ],
        ];

        $event = Webhook::parse($payload);

        $this->assertSame('evt_123', $event['id']);
        $this->assertSame('screenshot.completed', $event['type']);
        $this->assertInstanceOf(\DateTimeInterface::class, $event['timestamp']);
        $this->assertSame('https://example.com', $event['data']['url']);
        $this->assertSame('https://cdn.example.com/image.png', $event['data']['response']['url']);
        $this->assertSame(1200, $event['data']['response']['width']);
        $this->assertSame(630, $event['data']['response']['height']);
        $this->assertTrue($event['data']['response']['cached']);
    }

    public function testParseScreenshotFailed(): void
    {
        $payload = [
            'id' => 'evt_456',
            'event' => 'screenshot.failed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'url' => 'https://example.com',
                'error' => 'Page timeout',
            ],
        ];

        $event = Webhook::parse($payload);

        $this->assertSame('evt_456', $event['id']);
        $this->assertSame('screenshot.failed', $event['type']);
        $this->assertSame('https://example.com', $event['data']['url']);
        $this->assertSame('render_failed', $event['data']['error']['code']);
        $this->assertSame('Page timeout', $event['data']['error']['message']);
    }

    public function testParseBatchCompleted(): void
    {
        $payload = [
            'id' => 'batch_789',
            'event' => 'batch.completed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [],
        ];

        $event = Webhook::parse($payload);

        $this->assertSame('batch.completed', $event['type']);
        $this->assertSame('batch_789', $event['data']['batch_id']);
    }

    public function testParseStringPayload(): void
    {
        $payload = '{"id":"evt_123","event":"screenshot.completed","timestamp":"2024-01-15T10:30:00Z","data":{"screenshot_url":"https://cdn.example.com/image.png","width":1200,"height":630}}';

        $event = Webhook::parse($payload);

        $this->assertSame('evt_123', $event['id']);
        $this->assertSame('screenshot.completed', $event['type']);
    }

    public function testParseInvalidTimestamp(): void
    {
        $payload = [
            'id' => 'evt_123',
            'event' => 'screenshot.completed',
            'timestamp' => 'invalid',
            'data' => [],
        ];

        $event = Webhook::parse($payload);

        // Should use current time for invalid timestamps
        $this->assertInstanceOf(\DateTimeInterface::class, $event['timestamp']);
    }

    public function testExtractHeadersFromPhpServer(): void
    {
        $headers = [
            'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=abc123',
            'HTTP_X_WEBHOOK_TIMESTAMP' => '1234567890',
            'HTTP_HOST' => 'example.com',
        ];

        [$signature, $timestamp] = Webhook::extractHeaders($headers);

        $this->assertSame('sha256=abc123', $signature);
        $this->assertSame('1234567890', $timestamp);
    }

    public function testExtractHeadersExactMatch(): void
    {
        $headers = [
            'X-Webhook-Signature' => 'sha256=abc123',
            'X-Webhook-Timestamp' => '1234567890',
        ];

        [$signature, $timestamp] = Webhook::extractHeaders($headers);

        $this->assertSame('sha256=abc123', $signature);
        $this->assertSame('1234567890', $timestamp);
    }

    public function testExtractHeadersLowercase(): void
    {
        $headers = [
            'x-webhook-signature' => 'sha256=abc123',
            'x-webhook-timestamp' => '1234567890',
        ];

        [$signature, $timestamp] = Webhook::extractHeaders($headers);

        $this->assertSame('sha256=abc123', $signature);
        $this->assertSame('1234567890', $timestamp);
    }

    public function testExtractHeadersArrayValues(): void
    {
        // Some frameworks return headers as arrays
        $headers = [
            'x-webhook-signature' => ['sha256=abc123'],
            'x-webhook-timestamp' => ['1234567890'],
        ];

        [$signature, $timestamp] = Webhook::extractHeaders($headers);

        $this->assertSame('sha256=abc123', $signature);
        $this->assertSame('1234567890', $timestamp);
    }

    public function testExtractHeadersMissing(): void
    {
        $headers = [];

        [$signature, $timestamp] = Webhook::extractHeaders($headers);

        $this->assertSame('', $signature);
        $this->assertSame('', $timestamp);
    }
}
