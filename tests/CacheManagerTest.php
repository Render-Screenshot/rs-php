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

class CacheManagerTest extends TestCase
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

    public function testGetReturnsBinaryData(): void
    {
        $imageData = 'PNG binary data';
        $container = [];
        $client = $this->createMockClient([
            new Response(200, ['Content-Type' => 'image/png'], $imageData),
        ], $container);

        $result = $client->cache->get('cache_xyz789');

        $this->assertSame($imageData, $result);
        $this->assertSame('GET', $container[0]['request']->getMethod());
        $this->assertStringContainsString('cache/cache_xyz789', $container[0]['request']->getUri()->getPath());
    }

    public function testGetReturnsNullFor404(): void
    {
        $client = $this->createMockClient([
            new Response(404, [], json_encode(['code' => 'not_found', 'message' => 'Not found'])),
        ]);

        $result = $client->cache->get('cache_notfound');

        $this->assertNull($result);
    }

    public function testGetThrowsOnOtherErrors(): void
    {
        $client = $this->createMockClient([
            new Response(500, [], json_encode(['code' => 'internal_error', 'message' => 'Server error'])),
        ]);

        $this->expectException(RenderScreenshotException::class);

        $client->cache->get('cache_xyz');
    }

    public function testDeleteReturnsTrue(): void
    {
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['deleted' => true])),
        ], $container);

        $result = $client->cache->delete('cache_xyz789');

        $this->assertTrue($result);
        $this->assertSame('DELETE', $container[0]['request']->getMethod());
    }

    public function testDeleteReturnsFalse(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['deleted' => false])),
        ]);

        $result = $client->cache->delete('cache_notfound');

        $this->assertFalse($result);
    }

    public function testPurge(): void
    {
        $response = ['purged' => 3, 'keys' => ['cache_a', 'cache_b', 'cache_c']];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->cache->purge(['cache_a', 'cache_b', 'cache_c']);

        $this->assertSame(3, $result['purged']);
        $this->assertCount(3, $result['keys']);

        $this->assertSame('POST', $container[0]['request']->getMethod());
        $this->assertStringContainsString('cache/purge', $container[0]['request']->getUri()->getPath());

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertSame(['cache_a', 'cache_b', 'cache_c'], $body['keys']);
    }

    public function testPurgeUrl(): void
    {
        $response = ['purged' => 5, 'keys' => []];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->cache->purgeUrl('https://mysite.com/blog/*');

        $this->assertSame(5, $result['purged']);

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertSame('https://mysite.com/blog/*', $body['url']);
    }

    public function testPurgeBefore(): void
    {
        $response = ['purged' => 10, 'keys' => []];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $date = new DateTime('2024-01-01T00:00:00+00:00');
        $result = $client->cache->purgeBefore($date);

        $this->assertSame(10, $result['purged']);

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertStringContainsString('2024-01-01', $body['before']);
    }

    public function testPurgePattern(): void
    {
        $response = ['purged' => 2, 'keys' => []];
        $container = [];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($response)),
        ], $container);

        $result = $client->cache->purgePattern('screenshots/2024/*');

        $this->assertSame(2, $result['purged']);

        $body = json_decode($container[0]['request']->getBody()->getContents(), true);
        $this->assertSame('screenshots/2024/*', $body['pattern']);
    }
}
