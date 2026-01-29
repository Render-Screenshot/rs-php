<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests\Examples;

use DateTime;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RenderScreenshot\Client;
use RenderScreenshot\TakeOptions;

/**
 * Tests that mirror the Quick Start documentation examples.
 */
class QuickStartTest extends TestCase
{
    private function createMockClient(Response $response): Client
    {
        $mock = new MockHandler([$response]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        return new Client('rs_test_xxxxx', 'https://api.test.com', 30.0, $httpClient);
    }

    public function testBasicScreenshot(): void
    {
        // Example from Quick Start documentation
        $client = $this->createMockClient(new Response(200, [], 'PNG data'));

        // Take a screenshot
        $image = $client->take(
            TakeOptions::url('https://example.com')->preset('og_card')
        );

        // Verify we got image data
        $this->assertNotEmpty($image);
    }

    public function testScreenshotWithOptions(): void
    {
        $client = $this->createMockClient(new Response(200, [], 'PNG data'));

        // Take a screenshot with options
        $options = TakeOptions::url('https://example.com')
            ->width(1200)
            ->height(630)
            ->format('png')
            ->blockAds()
            ->blockTrackers()
            ->darkMode();

        $image = $client->take($options);

        $this->assertNotEmpty($image);
    }

    public function testGenerateSignedUrl(): void
    {
        $mock = new MockHandler([]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new Client('rs_test_xxxxx', 'https://api.test.com', 30.0, $httpClient);

        // Generate a signed URL for embedding
        $url = $client->generateUrl(
            TakeOptions::url('https://example.com')->preset('og_card'),
            new DateTime('+24 hours')
        );

        // Use in HTML: <img src="{$url}" />
        $this->assertStringStartsWith('https://api.test.com/v1/screenshot?', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function testSaveToFile(): void
    {
        $client = $this->createMockClient(new Response(200, [], 'PNG binary data'));

        $image = $client->take(
            TakeOptions::url('https://example.com')->preset('og_card')
        );

        // Would save to file in real usage:
        // file_put_contents('screenshot.png', $image);
        $this->assertSame('PNG binary data', $image);
    }

    public function testJsonResponse(): void
    {
        $responseData = [
            'url' => 'https://cdn.example.com/image.png',
            'width' => 1200,
            'height' => 630,
            'format' => 'png',
            'size' => 12345,
            'cached' => false,
        ];
        $client = $this->createMockClient(new Response(200, [], json_encode($responseData)));

        $response = $client->takeJson(
            TakeOptions::url('https://example.com')->preset('og_card')
        );

        $this->assertSame('https://cdn.example.com/image.png', $response['url']);
        $this->assertSame(1200, $response['width']);
        $this->assertSame(630, $response['height']);
    }
}
