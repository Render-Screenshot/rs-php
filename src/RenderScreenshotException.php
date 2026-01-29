<?php

declare(strict_types=1);

namespace RenderScreenshot;

use Exception;

/**
 * Custom exception for RenderScreenshot API errors.
 *
 * @example
 * try {
 *     $image = $client->take($options);
 * } catch (RenderScreenshotException $e) {
 *     if ($e->retryable && $e->retryAfter) {
 *         sleep($e->retryAfter);
 *         // Retry the request
 *     } else {
 *         throw $e;
 *     }
 * }
 */
class RenderScreenshotException extends Exception
{
    /**
     * Error codes that can be retried.
     */
    private const RETRYABLE_ERRORS = [
        'rate_limited',
        'timeout',
        'render_failed',
        'internal_error',
    ];

    /**
     * HTTP status code from the response.
     */
    public readonly int $httpStatus;

    /**
     * Error code from the API (e.g., "invalid_url", "rate_limited").
     */
    public readonly string $code;

    /**
     * Whether this error can be retried.
     */
    public readonly bool $retryable;

    /**
     * Seconds to wait before retrying (for rate limits).
     */
    public readonly ?int $retryAfter;

    /**
     * Create a new RenderScreenshotException.
     *
     * @param int      $httpStatus HTTP status code from the response
     * @param string   $code       Error code from the API
     * @param string   $message    Human-readable error message
     * @param int|null $retryAfter Seconds to wait before retrying (optional)
     */
    public function __construct(
        int $httpStatus,
        string $code,
        string $message,
        ?int $retryAfter = null
    ) {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->code = $code;
        $this->retryable = in_array($code, self::RETRYABLE_ERRORS, true);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Return string representation of the error.
     */
    public function __toString(): string
    {
        $parts = ["[{$this->code}] {$this->message}"];
        if ($this->retryAfter !== null) {
            $parts[] = " (retry after {$this->retryAfter}s)";
        }

        return implode('', $parts);
    }

    /**
     * Create an error from an API response.
     *
     * @param int                  $httpStatus HTTP status code from the response
     * @param array<string, mixed> $body       Parsed JSON response body
     * @param int|null             $retryAfter Value from Retry-After header (optional)
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function fromResponse(
        int $httpStatus,
        array $body,
        ?int $retryAfter = null
    ): self {
        $code = $body['code'] ?? 'internal_error';
        $message = $body['message'] ?? $body['error'] ?? 'An unknown error occurred';

        return new self($httpStatus, $code, $message, $retryAfter);
    }

    /**
     * Create an invalid URL error.
     *
     * @param string $url The invalid URL that was provided
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function invalidUrl(string $url): self
    {
        return new self(400, 'invalid_url', "Invalid URL provided: {$url}");
    }

    /**
     * Create an invalid request error.
     *
     * @param string $message Description of what was invalid
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function invalidRequest(string $message): self
    {
        return new self(400, 'invalid_request', $message);
    }

    /**
     * Create an unauthorized error.
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function unauthorized(): self
    {
        return new self(401, 'unauthorized', 'Invalid or missing API key');
    }

    /**
     * Create a forbidden error.
     *
     * @param string|null $message Optional custom message
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function forbidden(?string $message = null): self
    {
        return new self(403, 'forbidden', $message ?? 'Access denied');
    }

    /**
     * Create a not found error.
     *
     * @param string|null $message Optional custom message
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function notFound(?string $message = null): self
    {
        return new self(404, 'not_found', $message ?? 'Resource not found');
    }

    /**
     * Create a rate limited error.
     *
     * @param int|null $retryAfter Seconds to wait before retrying
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function rateLimited(?int $retryAfter = null): self
    {
        return new self(
            429,
            'rate_limited',
            'Rate limit exceeded. Please wait before making more requests.',
            $retryAfter
        );
    }

    /**
     * Create a timeout error.
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function timeout(): self
    {
        return new self(408, 'timeout', 'Screenshot request timed out');
    }

    /**
     * Create a render failed error.
     *
     * @param string|null $message Optional error details
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function renderFailed(?string $message = null): self
    {
        return new self(500, 'render_failed', $message ?? 'Browser rendering failed');
    }

    /**
     * Create an internal error.
     *
     * @param string|null $message Optional error details
     *
     * @return self A new RenderScreenshotException instance
     */
    public static function internal(?string $message = null): self
    {
        return new self(500, 'internal_error', $message ?? 'An internal error occurred');
    }
}
