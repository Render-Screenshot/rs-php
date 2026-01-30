<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests;

use DateTime;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RenderScreenshot\Client;
use RenderScreenshot\RenderScreenshotException;
use RenderScreenshot\TakeOptions;

class ClientTest extends TestCase
{
    private const API_KEY = 'rs_test_xxxxx';

    /**
     * Create a client with a mock handler.
     *
     * @param array<Response> $responses Responses to return
     * @param array<array>    $container Reference to capture requests
     */
    private function createMockClient(array $responses, array &$container = []): Client
    {
        $mock = new MockHandler($responses);
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        return new Client(self::API_KEY, 'https://api.test.com', 30.0, $httpClient);
    }

    public function testConstructorRequiresApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is required');

        new Client('');
    }

    public function testTakeReturnsImageData(): void
    {
        $imageData = 'PNG binary data here...';
        $container = [];
        $client = $this->createMockClient([
            new Response(200, ['Content-Type' => 'image/png'], $imageData),
        ], $container);

        $options = TakeOptions::url('https://example.com')->preset('og_card');
        $result = $client->take($options);

        $this->assertSame($imageData, $result);
        $this->assertCount(1, $container);
        $this->assertSame('POST', $container[0]['request']->getMethod());
        $this->assertStringContainsString('screenshot', $container[0]['request']->getUri()->getPath());
    }

    public function testTakeWithConfigArray(): void
    {
        $imageData = 'PNG data';
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], $imageData),
        ], $container);

        $result = $client->take(['url' => 'https://example.com', 'width' => 1200]);

        $this->assertSame($imageData, $result);
    }

    public function testTakeJsonReturnsMetadata(): void
    {
        $response = [
            'url' => 'https://cdn.example.com/image.png',
            'width' => 1200,
            'height' => 630,
            'format' => 'png',
            'size' => 12345,
            'cached' => false,
        ];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($response)),
        ], $container);

        $options = TakeOptions::url('https://example.com')->preset('og_card');
        $result = $client->takeJson($options);

        $this->assertSame($response['url'], $result['url']);
        $this->assertSame($response['width'], $result['width']);
        $this->assertSame($response['height'], $result['height']);

        // Verify response_type was added
        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertSame('json', $body['response_type']);
    }

    public function testGenerateUrl(): void
    {
        $container = [];
        $client = $this->createMockClient([], $container);

        $options = TakeOptions::url('https://example.com')
            ->preset('og_card')
            ->blockAds(true);

        $expiresAt = new DateTime('+24 hours');
        $url = $client->generateUrl($options, $expiresAt);

        $this->assertStringStartsWith('https://api.test.com/v1/screenshot?', $url);
        $this->assertStringContainsString('url=https://example.com', $url);
        $this->assertStringContainsString('preset=og_card', $url);
        $this->assertStringContainsString('block_ads=true', $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function testGenerateUrlWithConfigArray(): void
    {
        $client = $this->createMockClient([]);

        $url = $client->generateUrl(
            ['url' => 'https://example.com', 'width' => 1200],
            new DateTime('+1 hour')
        );

        $this->assertStringContainsString('width=1200', $url);
    }

    public function testBatch(): void
    {
        $response = [
            'id' => 'batch_123',
            'status' => 'completed',
            'total' => 2,
            'completed' => 2,
            'failed' => 0,
            'results' => [
                ['url' => 'https://example1.com', 'success' => true],
                ['url' => 'https://example2.com', 'success' => true],
            ],
        ];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->batch(
            ['https://example1.com', 'https://example2.com'],
            TakeOptions::url('')->preset('og_card')
        );

        $this->assertSame('batch_123', $result['id']);
        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['results']);

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertSame(['https://example1.com', 'https://example2.com'], $body['urls']);
        $this->assertArrayHasKey('options', $body);
    }

    public function testBatchAdvanced(): void
    {
        $response = [
            'id' => 'batch_456',
            'status' => 'completed',
            'total' => 2,
            'completed' => 2,
            'failed' => 0,
            'results' => [],
        ];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->batchAdvanced([
            ['url' => 'https://example1.com', 'options' => ['width' => 1200]],
            ['url' => 'https://example2.com', 'options' => ['preset' => 'full_page']],
        ]);

        $this->assertSame('batch_456', $result['id']);

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertArrayHasKey('requests', $body);
        $this->assertCount(2, $body['requests']);
    }

    public function testGetBatch(): void
    {
        $response = [
            'id' => 'batch_789',
            'status' => 'processing',
            'total' => 10,
            'completed' => 5,
            'failed' => 0,
            'results' => [],
        ];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->getBatch('batch_789');

        $this->assertSame('batch_789', $result['id']);
        $this->assertSame('processing', $result['status']);

        $this->assertSame('GET', $container[0]['request']->getMethod());
        $this->assertStringContainsString('batch/batch_789', $container[0]['request']->getUri()->getPath());
    }

    public function testPresets(): void
    {
        $response = [
            ['id' => 'og_card', 'name' => 'OG Card', 'width' => 1200, 'height' => 630],
            ['id' => 'twitter_card', 'name' => 'Twitter Card', 'width' => 1200, 'height' => 628],
        ];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ]);

        $result = $client->presets();

        $this->assertCount(2, $result);
        $this->assertSame('og_card', $result[0]['id']);
    }

    public function testPreset(): void
    {
        $response = ['id' => 'og_card', 'name' => 'OG Card', 'width' => 1200, 'height' => 630];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->preset('og_card');

        $this->assertSame('og_card', $result['id']);
        $this->assertStringContainsString('presets/og_card', $container[0]['request']->getUri()->getPath());
    }

    public function testDevices(): void
    {
        $response = [
            ['id' => 'iphone_14_pro', 'name' => 'iPhone 14 Pro', 'width' => 393, 'height' => 852, 'mobile' => true],
        ];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ]);

        $result = $client->devices();

        $this->assertCount(1, $result);
        $this->assertSame('iphone_14_pro', $result[0]['id']);
    }

    public function testHandlesApiError(): void
    {
        $client = $this->createMockClient([
            new Response(400, [], json_encode([
                'code' => 'invalid_url',
                'message' => 'The URL is not valid',
            ])),
        ]);

        $this->expectException(RenderScreenshotException::class);
        $this->expectExceptionMessage('The URL is not valid');

        $client->take(TakeOptions::url('not-a-url'));
    }

    public function testHandlesRateLimitWithRetryAfter(): void
    {
        $client = $this->createMockClient([
            new Response(429, ['Retry-After' => '60'], json_encode([
                'code' => 'rate_limited',
                'message' => 'Rate limit exceeded',
            ])),
        ]);

        try {
            $client->take(TakeOptions::url('https://example.com'));
            $this->fail('Expected RenderScreenshotException');
        } catch (RenderScreenshotException $e) {
            $this->assertSame(429, $e->httpStatus);
            $this->assertSame('rate_limited', $e->errorCode);
            $this->assertSame(60, $e->retryAfter);
            $this->assertTrue($e->retryable);
        }
    }

    public function testCachePropertyExists(): void
    {
        $client = $this->createMockClient([]);

        $this->assertInstanceOf(\RenderScreenshot\CacheManager::class, $client->cache);
    }
}
