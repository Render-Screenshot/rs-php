# RenderScreenshot PHP SDK

Official PHP SDK for [RenderScreenshot](https://renderscreenshot.com) - Screenshot API for developers.

[![CI](https://github.com/renderscreenshot/rs-php/actions/workflows/ci.yml/badge.svg)](https://github.com/renderscreenshot/rs-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/renderscreenshot/renderscreenshot/v)](https://packagist.org/packages/renderscreenshot/renderscreenshot)
[![License](https://poser.pugx.org/renderscreenshot/renderscreenshot/license)](https://packagist.org/packages/renderscreenshot/renderscreenshot)

## Installation

```bash
composer require renderscreenshot/renderscreenshot
```

## Requirements

- PHP 8.0 or higher
- Guzzle 7.0 or higher

## Quick Start

```php
use RenderScreenshot\Client;
use RenderScreenshot\TakeOptions;

$client = new Client('rs_live_xxxxx');

// Take a screenshot
$image = $client->take(
    TakeOptions::url('https://example.com')->preset('og_card')
);

// Save to file
file_put_contents('screenshot.png', $image);
```

## Features

- **Fluent API** - Chainable option builder for clean, readable code
- **Type safety** - Full type hints for IDE autocompletion
- **Webhook helpers** - Easy signature verification and parsing
- **Signed URLs** - Generate time-limited URLs for embedding
- **Batch processing** - Capture multiple URLs efficiently
- **Cache management** - Retrieve, delete, and purge cached screenshots

## Usage

### Basic Screenshot

```php
$client = new Client('rs_live_xxxxx');

// Using fluent builder
$options = TakeOptions::url('https://example.com')
    ->width(1200)
    ->height(630)
    ->format('png')
    ->blockAds()
    ->darkMode();

$image = $client->take($options);
file_put_contents('screenshot.png', $image);
```

### Get JSON Metadata

```php
$response = $client->takeJson(
    TakeOptions::url('https://example.com')->preset('og_card')
);

echo $response['url'];     // CDN URL
echo $response['width'];   // 1200
echo $response['height'];  // 630
echo $response['cached'];  // true/false
```

### Generate Signed URL

```php
use DateTime;

$url = $client->generateUrl(
    TakeOptions::url('https://example.com')->preset('og_card'),
    new DateTime('+24 hours')
);

// Use in HTML: <img src="<?= $url ?>" />
```

### Batch Processing

```php
// Simple batch with shared options
$results = $client->batch(
    ['https://example1.com', 'https://example2.com'],
    TakeOptions::url('')->preset('og_card')
);

// Advanced batch with per-URL options
$results = $client->batchAdvanced([
    ['url' => 'https://example1.com', 'options' => ['width' => 1200]],
    ['url' => 'https://example2.com', 'options' => ['preset' => 'full_page']],
]);

// Poll for completion
$status = $client->getBatch($results['id']);
echo $status['completed'] . '/' . $status['total'];
```

### PDF Generation

```php
$pdf = $client->take(
    TakeOptions::url('https://example.com')
        ->format('pdf')
        ->pdfPaperSize('a4')
        ->pdfLandscape()
        ->pdfMargin('1in')
        ->pdfPrintBackground()
);

file_put_contents('document.pdf', $pdf);
```

### Cache Management

```php
// Get cached screenshot
$image = $client->cache->get('cache_xyz789');

// Delete single entry
$deleted = $client->cache->delete('cache_xyz789');

// Bulk purge by keys
$client->cache->purge(['cache_abc', 'cache_def']);

// Purge by URL pattern
$client->cache->purgeUrl('https://mysite.com/blog/*');

// Purge by date
$client->cache->purgeBefore(new DateTime('2024-01-01'));
```

### Webhook Verification

```php
use RenderScreenshot\Webhook;

// Get webhook data
$payload = file_get_contents('php://input');
[$signature, $timestamp] = Webhook::extractHeaders($_SERVER);

// Verify signature
if (!Webhook::verify($payload, $signature, $timestamp, $webhookSecret)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Parse event
$event = Webhook::parse($payload);

if ($event['type'] === 'screenshot.completed') {
    $url = $event['data']['response']['url'];
    // Process completed screenshot...
}
```

## TakeOptions Reference

### Viewport

```php
TakeOptions::url('...')
    ->width(1200)          // Viewport width
    ->height(630)          // Viewport height
    ->scale(2)             // Device scale (1-3)
    ->mobile()             // Mobile emulation
```

### Capture

```php
TakeOptions::url('...')
    ->fullPage()           // Full scrollable page
    ->element('.hero')     // Specific element
    ->format('png')        // png, jpeg, webp, pdf
    ->quality(90)          // JPEG/WebP quality (1-100)
```

### Wait

```php
TakeOptions::url('...')
    ->waitFor('networkidle')      // load, networkidle, domcontentloaded
    ->delay(2000)                 // Additional delay (ms)
    ->waitForSelector('.loaded')  // Wait for selector
    ->waitForTimeout(30000)       // Max wait time (ms)
```

### Presets & Devices

```php
TakeOptions::url('...')
    ->preset('og_card')           // Use a preset
    ->device('iphone_14_pro')     // Emulate a device
```

### Content Blocking

```php
TakeOptions::url('...')
    ->blockAds()                  // Block ad networks
    ->blockTrackers()             // Block analytics
    ->blockCookieBanners()        // Auto-dismiss cookies
    ->blockChatWidgets()          // Block chat widgets
    ->blockUrls(['*.ads.com/*'])  // Block URL patterns
    ->blockResources(['font'])    // Block resource types
```

### Browser Emulation

```php
TakeOptions::url('...')
    ->darkMode()                  // Dark mode
    ->reducedMotion()             // Reduced motion
    ->mediaType('print')          // screen or print
    ->userAgent('Custom UA')      // Custom user agent
    ->timezone('America/New_York')
    ->locale('en-US')
    ->geolocation(40.7128, -74.006)
```

### Network

```php
TakeOptions::url('...')
    ->headers(['X-Custom' => 'value'])
    ->cookies([['name' => 'session', 'value' => 'abc', 'domain' => 'example.com']])
    ->authBasic('user', 'pass')
    ->authBearer('token')
    ->bypassCsp()
```

## Error Handling

```php
use RenderScreenshot\RenderScreenshotException;

try {
    $client->take(TakeOptions::url('https://example.com'));
} catch (RenderScreenshotException $e) {
    echo $e->httpStatus;   // 400, 429, etc.
    echo $e->errorCode;    // 'invalid_url', 'rate_limited'
    echo $e->message;      // Human-readable message
    echo $e->retryable;    // true/false
    echo $e->retryAfter;   // Seconds (for rate limits)

    if ($e->retryable && $e->retryAfter) {
        sleep($e->retryAfter);
        // Retry...
    }
}
```

## Links

- [Documentation](https://renderscreenshot.com/docs/sdks/php)
- [API Reference](https://renderscreenshot.com/docs/endpoints/post-screenshot)
- [GitHub](https://github.com/renderscreenshot/rs-php)

## License

MIT
