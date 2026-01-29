<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests;

use PHPUnit\Framework\TestCase;
use RenderScreenshot\RenderScreenshotException;

class RenderScreenshotExceptionTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $e = new RenderScreenshotException(400, 'invalid_url', 'Bad URL', 60);

        $this->assertSame(400, $e->httpStatus);
        $this->assertSame('invalid_url', $e->code);
        $this->assertSame('Bad URL', $e->getMessage());
        $this->assertSame(60, $e->retryAfter);
        $this->assertFalse($e->retryable);
    }

    public function testRetryableErrors(): void
    {
        $retryableCodes = ['rate_limited', 'timeout', 'render_failed', 'internal_error'];
        foreach ($retryableCodes as $code) {
            $e = new RenderScreenshotException(500, $code, 'test');
            $this->assertTrue($e->retryable, "Code '{$code}' should be retryable");
        }

        $nonRetryableCodes = ['invalid_url', 'unauthorized', 'forbidden', 'not_found'];
        foreach ($nonRetryableCodes as $code) {
            $e = new RenderScreenshotException(400, $code, 'test');
            $this->assertFalse($e->retryable, "Code '{$code}' should not be retryable");
        }
    }

    public function testToString(): void
    {
        $e = new RenderScreenshotException(429, 'rate_limited', 'Too many requests', 60);
        $this->assertSame('[rate_limited] Too many requests (retry after 60s)', (string) $e);

        $e2 = new RenderScreenshotException(400, 'invalid_url', 'Bad URL');
        $this->assertSame('[invalid_url] Bad URL', (string) $e2);
    }

    public function testFromResponse(): void
    {
        $e = RenderScreenshotException::fromResponse(400, [
            'code' => 'invalid_url',
            'message' => 'The URL is invalid',
        ]);

        $this->assertSame(400, $e->httpStatus);
        $this->assertSame('invalid_url', $e->code);
        $this->assertSame('The URL is invalid', $e->getMessage());
    }

    public function testFromResponseWithErrorField(): void
    {
        $e = RenderScreenshotException::fromResponse(500, [
            'code' => 'internal_error',
            'error' => 'Something went wrong',
        ]);

        $this->assertSame('Something went wrong', $e->getMessage());
    }

    public function testFromResponseWithMissingFields(): void
    {
        $e = RenderScreenshotException::fromResponse(500, []);

        $this->assertSame('internal_error', $e->code);
        $this->assertSame('An unknown error occurred', $e->getMessage());
    }

    public function testFromResponseWithRetryAfter(): void
    {
        $e = RenderScreenshotException::fromResponse(429, [
            'code' => 'rate_limited',
            'message' => 'Too many requests',
        ], 120);

        $this->assertSame(120, $e->retryAfter);
        $this->assertTrue($e->retryable);
    }

    public function testInvalidUrl(): void
    {
        $e = RenderScreenshotException::invalidUrl('not-a-url');

        $this->assertSame(400, $e->httpStatus);
        $this->assertSame('invalid_url', $e->code);
        $this->assertStringContainsString('not-a-url', $e->getMessage());
    }

    public function testInvalidRequest(): void
    {
        $e = RenderScreenshotException::invalidRequest('Missing required field');

        $this->assertSame(400, $e->httpStatus);
        $this->assertSame('invalid_request', $e->code);
        $this->assertSame('Missing required field', $e->getMessage());
    }

    public function testUnauthorized(): void
    {
        $e = RenderScreenshotException::unauthorized();

        $this->assertSame(401, $e->httpStatus);
        $this->assertSame('unauthorized', $e->code);
        $this->assertStringContainsString('API key', $e->getMessage());
    }

    public function testForbidden(): void
    {
        $e = RenderScreenshotException::forbidden();
        $this->assertSame(403, $e->httpStatus);
        $this->assertSame('forbidden', $e->code);
        $this->assertSame('Access denied', $e->getMessage());

        $e2 = RenderScreenshotException::forbidden('Custom message');
        $this->assertSame('Custom message', $e2->getMessage());
    }

    public function testNotFound(): void
    {
        $e = RenderScreenshotException::notFound();
        $this->assertSame(404, $e->httpStatus);
        $this->assertSame('not_found', $e->code);
        $this->assertSame('Resource not found', $e->getMessage());

        $e2 = RenderScreenshotException::notFound('Preset not found');
        $this->assertSame('Preset not found', $e2->getMessage());
    }

    public function testRateLimited(): void
    {
        $e = RenderScreenshotException::rateLimited();
        $this->assertSame(429, $e->httpStatus);
        $this->assertSame('rate_limited', $e->code);
        $this->assertTrue($e->retryable);
        $this->assertNull($e->retryAfter);

        $e2 = RenderScreenshotException::rateLimited(60);
        $this->assertSame(60, $e2->retryAfter);
    }

    public function testTimeout(): void
    {
        $e = RenderScreenshotException::timeout();

        $this->assertSame(408, $e->httpStatus);
        $this->assertSame('timeout', $e->code);
        $this->assertTrue($e->retryable);
    }

    public function testRenderFailed(): void
    {
        $e = RenderScreenshotException::renderFailed();
        $this->assertSame(500, $e->httpStatus);
        $this->assertSame('render_failed', $e->code);
        $this->assertTrue($e->retryable);

        $e2 = RenderScreenshotException::renderFailed('Page crashed');
        $this->assertSame('Page crashed', $e2->getMessage());
    }

    public function testInternal(): void
    {
        $e = RenderScreenshotException::internal();
        $this->assertSame(500, $e->httpStatus);
        $this->assertSame('internal_error', $e->code);
        $this->assertTrue($e->retryable);

        $e2 = RenderScreenshotException::internal('Database error');
        $this->assertSame('Database error', $e2->getMessage());
    }
}
