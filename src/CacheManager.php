<?php

declare(strict_types=1);

namespace RenderScreenshot;

use DateTimeInterface;

/**
 * Cache management methods for RenderScreenshot.
 *
 * This class provides methods to retrieve, delete, and purge cached screenshots.
 * Access it via the `cache` property on the Client instance.
 *
 * @example
 * $client = new Client('rs_live_xxxxx');
 *
 * // Get a cached screenshot
 * $image = $client->cache->get('cache_xyz789');
 *
 * // Delete a cache entry
 * $deleted = $client->cache->delete('cache_xyz789');
 *
 * // Bulk purge
 * $result = $client->cache->purge(['cache_abc', 'cache_def']);
 */
class CacheManager
{
    /**
     * The parent Client instance.
     */
    private Client $client;

    /**
     * Create a new CacheManager.
     *
     * @param Client $client The parent Client instance
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get a cached screenshot by its cache key.
     *
     * @param string $key The cache key returned in X-Cache-Key header or response
     *
     * @return string|null Binary data containing the screenshot, or null if not found
     *
     * @example
     * $image = $client->cache->get('cache_xyz789');
     * if ($image !== null) {
     *     file_put_contents('cached.png', $image);
     * }
     */
    public function get(string $key): ?string
    {
        try {
            /** @var string */
            return $this->client->request('GET', "/cache/{$key}", null, 'buffer');
        } catch (RenderScreenshotException $e) {
            if ($e->httpStatus === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Delete a single cache entry.
     *
     * @param string $key The cache key to delete
     *
     * @return bool True if deleted, false if not found
     *
     * @example
     * $deleted = $client->cache->delete('cache_xyz789');
     * echo $deleted ? 'Deleted' : 'Not found';
     */
    public function delete(string $key): bool
    {
        /** @var array{deleted?: bool} $response */
        $response = $this->client->request('DELETE', "/cache/{$key}");

        return $response['deleted'] ?? false;
    }

    /**
     * Bulk purge cache entries by keys.
     *
     * @param array<string> $keys List of cache keys to purge
     *
     * @return array{purged: int, keys: array<string>} Purge result with count and keys
     *
     * @example
     * $result = $client->cache->purge(['cache_abc', 'cache_def']);
     * echo "Purged {$result['purged']} entries";
     */
    public function purge(array $keys): array
    {
        /** @var array{purged: int, keys: array<string>} */
        return $this->client->request('POST', '/cache/purge', ['keys' => $keys]);
    }

    /**
     * Purge cache entries matching a URL pattern.
     *
     * @param string $pattern Glob pattern to match source URLs
     *
     * @return array{purged: int, keys: array<string>} Purge result with count
     *
     * @example
     * $result = $client->cache->purgeUrl('https://mysite.com/blog/*');
     * echo "Purged {$result['purged']} entries";
     */
    public function purgeUrl(string $pattern): array
    {
        /** @var array{purged: int, keys: array<string>} */
        return $this->client->request('POST', '/cache/purge', ['url' => $pattern]);
    }

    /**
     * Purge cache entries created before a specific date.
     *
     * @param DateTimeInterface $date Purge entries created before this date
     *
     * @return array{purged: int, keys: array<string>} Purge result with count
     *
     * @example
     * $result = $client->cache->purgeBefore(new DateTime('2024-01-01'));
     * echo "Purged {$result['purged']} entries";
     */
    public function purgeBefore(DateTimeInterface $date): array
    {
        /** @var array{purged: int, keys: array<string>} */
        return $this->client->request('POST', '/cache/purge', ['before' => $date->format('c')]);
    }

    /**
     * Purge cache entries matching a storage path pattern.
     *
     * @param string $pattern Glob pattern for storage paths
     *
     * @return array{purged: int, keys: array<string>} Purge result with count
     *
     * @example
     * $result = $client->cache->purgePattern('screenshots/2024/*');
     * echo "Purged {$result['purged']} entries";
     */
    public function purgePattern(string $pattern): array
    {
        /** @var array{purged: int, keys: array<string>} */
        return $this->client->request('POST', '/cache/purge', ['pattern' => $pattern]);
    }
}
