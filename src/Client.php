<?php

declare(strict_types=1);

namespace RenderScreenshot;

use DateTimeInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

/**
 * RenderScreenshot API client.
 *
 * Use this client to take screenshots, generate signed URLs, and manage cache.
 *
 * @example
 * use RenderScreenshot\Client;
 * use RenderScreenshot\TakeOptions;
 *
 * $client = new Client('rs_live_xxxxx');
 * $options = TakeOptions::url('https://example.com')->preset('og_card');
 * $image = $client->take($options);
 *
 * @property-read CacheManager $cache Cache management methods
 */
class Client
{
    private const DEFAULT_BASE_URL = 'https://api.renderscreenshot.com';
    private const DEFAULT_TIMEOUT = 30.0;
    private const API_VERSION = 'v1';
    private const SDK_VERSION = '1.0.0';

    /**
     * API key for authentication.
     */
    private string $apiKey;

    /**
     * Base URL for the API.
     */
    private string $baseUrl;

    /**
     * Request timeout in seconds.
     */
    private float $timeout;

    /**
     * HTTP client instance.
     */
    private ?GuzzleClient $httpClient;

    /**
     * Cache manager instance.
     */
    public CacheManager $cache;

    /**
     * Create a new RenderScreenshot client.
     *
     * @param string           $apiKey     Your API key (rs_live_* or rs_test_*)
     * @param string|null      $baseUrl    Base URL for the API (optional, defaults to production)
     * @param float|null       $timeout    Request timeout in seconds (optional, defaults to 30)
     * @param GuzzleClient|null $httpClient Custom Guzzle client for testing (optional)
     *
     * @throws \InvalidArgumentException If API key is empty
     *
     * @example
     * $client = new Client('rs_live_xxxxx');
     *
     * // Or with custom settings:
     * $client = new Client(
     *     'rs_live_xxxxx',
     *     'https://api.staging.renderscreenshot.com',
     *     60.0
     * );
     */
    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        ?float $timeout = null,
        ?GuzzleClient $httpClient = null
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;
        $this->httpClient = $httpClient;
        $this->cache = new CacheManager($this);
    }

    /**
     * Get or create the HTTP client.
     */
    private function getClient(): GuzzleClient
    {
        if ($this->httpClient === null) {
            $this->httpClient = new GuzzleClient([
                'base_uri' => "{$this->baseUrl}/" . self::API_VERSION . '/',
                'timeout' => $this->timeout,
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'User-Agent' => 'renderscreenshot-php/' . self::SDK_VERSION,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Make an authenticated request to the API.
     *
     * @param string                    $method       HTTP method (GET, POST, DELETE, etc.)
     * @param string                    $path         API path (e.g., "/screenshot")
     * @param array<string, mixed>|null $body         Request body for POST/PUT requests
     * @param string                    $responseType Expected response type ("json" or "buffer")
     *
     * @return mixed Parsed JSON response or string bytes
     *
     * @throws RenderScreenshotException If the API returns an error
     *
     * @internal This method is public for CacheManager access but not part of the public API
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        string $responseType = 'json'
    ): mixed {
        try {
            $options = [];

            if ($body !== null) {
                $options[RequestOptions::JSON] = $body;
            }

            // For binary responses, override Accept header
            if ($responseType === 'buffer') {
                $options[RequestOptions::HEADERS] = ['Accept' => '*/*'];
            }

            $response = $this->getClient()->request($method, ltrim($path, '/'), $options);
            $statusCode = $response->getStatusCode();

            // Handle errors
            if ($statusCode >= 400) {
                $this->handleError($response->getStatusCode(), $response->getBody()->getContents(), $response->getHeaders());
            }

            // Return appropriate type
            $contents = $response->getBody()->getContents();
            if ($responseType === 'buffer') {
                return $contents;
            }

            return json_decode($contents, true);
        } catch (ConnectException $e) {
            throw RenderScreenshotException::timeout();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $this->handleError(
                    $response->getStatusCode(),
                    $response->getBody()->getContents(),
                    $response->getHeaders()
                );
            }
            throw RenderScreenshotException::internal($e->getMessage());
        }
    }

    /**
     * Handle API error responses.
     *
     * @param int                            $statusCode HTTP status code
     * @param string                         $body       Response body
     * @param array<string, array<string>>   $headers    Response headers
     *
     * @throws RenderScreenshotException Always raised with error details
     */
    private function handleError(int $statusCode, string $body, array $headers): void
    {
        $retryAfter = null;
        if (isset($headers['Retry-After'][0])) {
            $retryAfter = (int) $headers['Retry-After'][0];
        } elseif (isset($headers['retry-after'][0])) {
            $retryAfter = (int) $headers['retry-after'][0];
        }

        try {
            $parsed = json_decode($body, true);
            if (!is_array($parsed)) {
                $parsed = ['message' => $body ?: 'Unknown error'];
            }
        } catch (\Exception) {
            $parsed = ['message' => $body ?: 'Unknown error'];
        }

        throw RenderScreenshotException::fromResponse($statusCode, $parsed, $retryAfter);
    }

    /**
     * Generate a signed URL for embedding screenshots.
     *
     * Signed URLs can be used in <img> tags or shared publicly.
     * They don't require API key authentication but expire at the specified time.
     *
     * @param TakeOptions|array<string, mixed> $options   Screenshot options (TakeOptions instance or config array)
     * @param DateTimeInterface                $expiresAt Expiration time for the signed URL (max 30 days)
     *
     * @return string Signed URL string
     *
     * @example
     * $options = TakeOptions::url('https://example.com')->preset('og_card');
     * $url = $client->generateUrl(
     *     $options,
     *     new DateTime('+24 hours')
     * );
     * // Use in HTML: <img src="{$url}" />
     */
    public function generateUrl(TakeOptions|array $options, DateTimeInterface $expiresAt): string
    {
        $config = $options instanceof TakeOptions ? $options->toConfig() : $options;

        // Build query params from config
        $params = [];

        if (isset($config['url'])) {
            $params['url'] = (string) $config['url'];
        }
        if (isset($config['width'])) {
            $params['width'] = (string) $config['width'];
        }
        if (isset($config['height'])) {
            $params['height'] = (string) $config['height'];
        }
        if (isset($config['scale'])) {
            $params['scale'] = (string) $config['scale'];
        }
        if (isset($config['mobile'])) {
            $params['mobile'] = $config['mobile'] ? 'true' : 'false';
        }
        if (isset($config['full_page'])) {
            $params['full_page'] = $config['full_page'] ? 'true' : 'false';
        }
        if (isset($config['element'])) {
            $params['element'] = (string) $config['element'];
        }
        if (isset($config['format'])) {
            $params['format'] = (string) $config['format'];
        }
        if (isset($config['quality'])) {
            $params['quality'] = (string) $config['quality'];
        }
        if (isset($config['preset'])) {
            $params['preset'] = (string) $config['preset'];
        }
        if (isset($config['device'])) {
            $params['device'] = (string) $config['device'];
        }
        if (isset($config['wait_for'])) {
            $params['wait_for'] = (string) $config['wait_for'];
        }
        if (isset($config['delay'])) {
            $params['delay'] = (string) $config['delay'];
        }
        if (isset($config['block_ads'])) {
            $params['block_ads'] = $config['block_ads'] ? 'true' : 'false';
        }
        if (isset($config['block_trackers'])) {
            $params['block_trackers'] = $config['block_trackers'] ? 'true' : 'false';
        }
        if (isset($config['block_cookie_banners'])) {
            $params['block_cookie_banners'] = $config['block_cookie_banners'] ? 'true' : 'false';
        }
        if (isset($config['block_chat_widgets'])) {
            $params['block_chat_widgets'] = $config['block_chat_widgets'] ? 'true' : 'false';
        }
        if (isset($config['dark_mode'])) {
            $params['dark_mode'] = $config['dark_mode'] ? 'true' : 'false';
        }
        if (isset($config['cache_ttl'])) {
            $params['cache_ttl'] = (string) $config['cache_ttl'];
        }

        // Sort params alphabetically and create canonical string
        ksort($params);
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = "{$key}={$value}";
        }
        $canonical = implode('&', $parts);

        // Add expiration
        $expires = $expiresAt->getTimestamp();
        $message = "{$canonical}&expires={$expires}";

        // Generate signature
        $signature = hash_hmac('sha256', $message, $this->apiKey);

        return "{$this->baseUrl}/" . self::API_VERSION . "/screenshot?{$message}&signature={$signature}";
    }

    /**
     * Take a screenshot and return the binary data.
     *
     * @param TakeOptions|array<string, mixed> $options Screenshot options (TakeOptions instance or config array)
     *
     * @return string Binary data containing the screenshot image or PDF
     *
     * @throws RenderScreenshotException If the screenshot fails
     *
     * @example
     * $options = TakeOptions::url('https://example.com')->preset('og_card');
     * $image = $client->take($options);
     * file_put_contents('screenshot.png', $image);
     */
    public function take(TakeOptions|array $options): string
    {
        $params = $options instanceof TakeOptions
            ? $options->toParams()
            : TakeOptions::fromConfig($options)->toParams();

        return $this->request('POST', '/screenshot', $params, 'buffer');
    }

    /**
     * Take a screenshot and return JSON metadata with URLs.
     *
     * @param TakeOptions|array<string, mixed> $options Screenshot options (TakeOptions instance or config array)
     *
     * @return array{
     *     url: string,
     *     cache_url?: string,
     *     width: int,
     *     height: int,
     *     format: string,
     *     size: int,
     *     cache_key?: string,
     *     ttl?: int,
     *     cached: bool,
     *     storage_path?: string
     * } Screenshot response with metadata and URLs
     *
     * @throws RenderScreenshotException If the screenshot fails
     *
     * @example
     * $options = TakeOptions::url('https://example.com')->preset('og_card');
     * $response = $client->takeJson($options);
     * echo "Screenshot URL: " . $response['url'];
     * echo "Size: " . $response['width'] . "x" . $response['height'];
     */
    public function takeJson(TakeOptions|array $options): array
    {
        $params = $options instanceof TakeOptions
            ? $options->toParams()
            : TakeOptions::fromConfig($options)->toParams();

        $params['response_type'] = 'json';

        return $this->request('POST', '/screenshot', $params);
    }

    /**
     * Process multiple screenshots in a batch.
     *
     * This method has two call signatures:
     *
     * 1. With a list of URLs and shared options:
     *    $results = $client->batch(
     *        ['https://example1.com', 'https://example2.com'],
     *        TakeOptions::url('')->preset('og_card')
     *    );
     *
     * 2. With a list of request items (per-URL options):
     *    $results = $client->batchAdvanced([
     *        ['url' => 'https://example1.com', 'options' => ['width' => 1200]],
     *        ['url' => 'https://example2.com', 'options' => ['preset' => 'full_page']],
     *    ]);
     *
     * @param array<string>                         $urls    List of URLs to capture
     * @param TakeOptions|array<string, mixed>|null $options Shared options for all URLs (optional)
     *
     * @return array{
     *     id: string,
     *     status: string,
     *     total: int,
     *     completed: int,
     *     failed: int,
     *     results: array<array{url: string, success: bool, response?: array<string, mixed>, error?: array<string, mixed>}>
     * } Batch response with results for each URL
     *
     * @throws RenderScreenshotException If the batch request fails
     *
     * @example
     * $results = $client->batch(
     *     ['https://example1.com', 'https://example2.com'],
     *     TakeOptions::url('')->preset('og_card')
     * );
     */
    public function batch(array $urls, TakeOptions|array|null $options = null): array
    {
        $opts = [];
        if ($options !== null) {
            $opts = $options instanceof TakeOptions
                ? $options->toParams()
                : TakeOptions::fromConfig($options)->toParams();
        }

        $body = [
            'urls' => $urls,
            'options' => $opts,
        ];

        return $this->request('POST', '/batch', $body);
    }

    /**
     * Process multiple screenshots with per-URL options.
     *
     * @param array<array{url: string, options?: array<string, mixed>}> $requests List of request items
     *
     * @return array{
     *     id: string,
     *     status: string,
     *     total: int,
     *     completed: int,
     *     failed: int,
     *     results: array<array{url: string, success: bool, response?: array<string, mixed>, error?: array<string, mixed>}>
     * } Batch response with results for each URL
     *
     * @throws RenderScreenshotException If the batch request fails
     *
     * @example
     * $results = $client->batchAdvanced([
     *     ['url' => 'https://example1.com', 'options' => ['width' => 1200]],
     *     ['url' => 'https://example2.com', 'options' => ['preset' => 'full_page']],
     * ]);
     */
    public function batchAdvanced(array $requests): array
    {
        $formattedRequests = [];
        foreach ($requests as $req) {
            $options = $req['options'] ?? [];
            $params = TakeOptions::fromConfig($options)->toParams();
            $formattedRequests[] = array_merge(['url' => $req['url']], $params);
        }

        return $this->request('POST', '/batch', ['requests' => $formattedRequests]);
    }

    /**
     * Get the status of a batch job.
     *
     * @param string $batchId The batch job ID
     *
     * @return array{
     *     id: string,
     *     status: string,
     *     total: int,
     *     completed: int,
     *     failed: int,
     *     results: array<array{url: string, success: bool, response?: array<string, mixed>, error?: array<string, mixed>}>
     * } Batch status and results
     *
     * @throws RenderScreenshotException If the batch is not found
     *
     * @example
     * $response = $client->getBatch('batch_abc123');
     * echo "Status: " . $response['status'];
     * echo "Completed: " . $response['completed'] . "/" . $response['total'];
     */
    public function getBatch(string $batchId): array
    {
        return $this->request('GET', "/batch/{$batchId}");
    }

    /**
     * List all available presets.
     *
     * @return array<array{id: string, name: string, description?: string, width: int, height: int, scale?: float, format?: string}>
     *     List of preset configurations
     *
     * @example
     * foreach ($client->presets() as $preset) {
     *     echo "{$preset['id']}: {$preset['description']}";
     * }
     */
    public function presets(): array
    {
        return $this->request('GET', '/presets');
    }

    /**
     * Get a single preset by ID.
     *
     * @param string $presetId Preset identifier (e.g., "og_card")
     *
     * @return array{id: string, name: string, description?: string, width: int, height: int, scale?: float, format?: string}
     *     Preset configuration
     *
     * @throws RenderScreenshotException If the preset is not found
     *
     * @example
     * $preset = $client->preset('og_card');
     * echo "Size: " . $preset['width'] . "x" . $preset['height'];
     */
    public function preset(string $presetId): array
    {
        return $this->request('GET', "/presets/{$presetId}");
    }

    /**
     * List all available device presets.
     *
     * @return array<array{id: string, name: string, width: int, height: int, scale: float, mobile: bool, user_agent: string}>
     *     List of device configurations
     *
     * @example
     * foreach ($client->devices() as $device) {
     *     echo "{$device['id']}: {$device['name']}";
     * }
     */
    public function devices(): array
    {
        return $this->request('GET', '/devices');
    }
}
