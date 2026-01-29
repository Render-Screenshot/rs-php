<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests\Examples;

use PHPUnit\Framework\TestCase;
use RenderScreenshot\Webhook;

/**
 * Tests that mirror the Webhooks documentation examples.
 */
class WebhooksTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    public function testVerifyAndParseWebhook(): void
    {
        // Simulate incoming webhook
        $payload = json_encode([
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
                'cached' => false,
            ],
        ]);

        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        // Verify signature (as shown in docs)
        $isValid = Webhook::verify($payload, $signature, $timestamp, self::WEBHOOK_SECRET);
        $this->assertTrue($isValid);

        // Parse event
        $event = Webhook::parse($payload);
        $this->assertSame('screenshot.completed', $event['type']);
        $this->assertSame('https://cdn.example.com/image.png', $event['data']['response']['url']);
    }

    public function testExtractHeadersFromPhpServer(): void
    {
        // Simulates $_SERVER in a typical PHP webhook handler
        $server = [
            'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=abc123',
            'HTTP_X_WEBHOOK_TIMESTAMP' => '1234567890',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json',
        ];

        [$signature, $timestamp] = Webhook::extractHeaders($server);

        $this->assertSame('sha256=abc123', $signature);
        $this->assertSame('1234567890', $timestamp);
    }

    public function testHandleScreenshotCompleted(): void
    {
        $event = Webhook::parse([
            'id' => 'evt_123',
            'event' => 'screenshot.completed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'url' => 'https://example.com',
                'screenshot_url' => 'https://cdn.example.com/image.png',
                'width' => 1200,
                'height' => 630,
            ],
        ]);

        if ($event['type'] === 'screenshot.completed') {
            $response = $event['data']['response'];
            $url = $response['url'];
            $this->assertSame('https://cdn.example.com/image.png', $url);
        }
    }

    public function testHandleScreenshotFailed(): void
    {
        $event = Webhook::parse([
            'id' => 'evt_456',
            'event' => 'screenshot.failed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'url' => 'https://example.com',
                'error' => 'Page timeout after 30s',
            ],
        ]);

        if ($event['type'] === 'screenshot.failed') {
            $error = $event['data']['error'];
            $this->assertSame('render_failed', $error['code']);
            $this->assertSame('Page timeout after 30s', $error['message']);
        }
    }

    public function testHandleBatchCompleted(): void
    {
        $event = Webhook::parse([
            'id' => 'batch_789',
            'event' => 'batch.completed',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [],
        ]);

        if ($event['type'] === 'batch.completed') {
            $batchId = $event['data']['batch_id'];
            $this->assertSame('batch_789', $batchId);
        }
    }
}
