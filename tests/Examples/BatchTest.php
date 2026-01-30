<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests\Examples;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RenderScreenshot\Client;
use RenderScreenshot\TakeOptions;

/**
 * Tests that mirror the Batch documentation examples.
 */
class BatchTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private static function jsonEncode(array $data): string
    {
        $json = json_encode($data);
        assert($json !== false);

        return $json;
    }

    private function createMockClient(Response $response): Client
    {
        $mock = new MockHandler([$response]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        return new Client('rs_test_xxxxx', 'https://api.test.com', 30.0, $httpClient);
    }

    public function testSimpleBatch(): void
    {
        $responseData = [
            'id' => 'batch_123',
            'status' => 'completed',
            'total' => 3,
            'completed' => 3,
            'failed' => 0,
            'results' => [
                ['url' => 'https://example1.com', 'success' => true],
                ['url' => 'https://example2.com', 'success' => true],
                ['url' => 'https://example3.com', 'success' => true],
            ],
        ];
        $client = $this->createMockClient(new Response(200, [], self::jsonEncode($responseData)));

        // Simple batch with shared options
        $results = $client->batch(
            ['https://example1.com', 'https://example2.com', 'https://example3.com'],
            TakeOptions::url('')->preset('og_card')
        );

        $this->assertSame('batch_123', $results['id']);
        $this->assertSame(3, $results['total']);
        $this->assertSame(3, $results['completed']);
    }

    public function testAdvancedBatch(): void
    {
        $responseData = [
            'id' => 'batch_456',
            'status' => 'completed',
            'total' => 2,
            'completed' => 2,
            'failed' => 0,
            'results' => [],
        ];
        $client = $this->createMockClient(new Response(200, [], self::jsonEncode($responseData)));

        // Advanced batch with per-URL options
        $results = $client->batchAdvanced([
            ['url' => 'https://example1.com', 'options' => ['width' => 1200, 'height' => 630]],
            ['url' => 'https://example2.com', 'options' => ['preset' => 'full_page']],
        ]);

        $this->assertSame('batch_456', $results['id']);
        $this->assertSame(2, $results['total']);
    }

    public function testBatchPolling(): void
    {
        // First call: processing
        $processingResponse = new Response(200, [], self::jsonEncode([
            'id' => 'batch_789',
            'status' => 'processing',
            'total' => 10,
            'completed' => 5,
            'failed' => 0,
            'results' => [],
        ]));

        // Second call: completed
        $completedResponse = new Response(200, [], self::jsonEncode([
            'id' => 'batch_789',
            'status' => 'completed',
            'total' => 10,
            'completed' => 10,
            'failed' => 0,
            'results' => [],
        ]));

        $mock = new MockHandler([$processingResponse, $completedResponse]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('rs_test_xxxxx', 'https://api.test.com', 30.0, $httpClient);

        // Check status
        $status1 = $client->getBatch('batch_789');
        $this->assertSame('processing', $status1['status']);
        $this->assertSame(5, $status1['completed']);

        // Poll again
        $status2 = $client->getBatch('batch_789');
        $this->assertSame('completed', $status2['status']);
        $this->assertSame(10, $status2['completed']);
    }
}
