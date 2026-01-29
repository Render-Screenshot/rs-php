# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-29

### Added

- Initial release of the RenderScreenshot PHP SDK
- `Client` class with full API support
  - `take()` - Take screenshot and return binary data
  - `takeJson()` - Take screenshot and return JSON metadata
  - `generateUrl()` - Generate signed URLs for embedding
  - `batch()` - Process multiple URLs with shared options
  - `batchAdvanced()` - Process multiple URLs with per-URL options
  - `getBatch()` - Get batch job status
  - `presets()` / `preset()` - List and get presets
  - `devices()` - List device presets
- `TakeOptions` fluent builder with 60+ methods
  - Viewport: `width()`, `height()`, `scale()`, `mobile()`
  - Capture: `fullPage()`, `element()`, `format()`, `quality()`
  - Wait: `waitFor()`, `delay()`, `waitForSelector()`, `waitForTimeout()`
  - Presets: `preset()`, `device()`
  - Blocking: `blockAds()`, `blockTrackers()`, `blockCookieBanners()`, `blockChatWidgets()`, `blockUrls()`, `blockResources()`
  - Page: `injectScript()`, `injectStyle()`, `click()`, `hide()`, `remove()`
  - Browser: `darkMode()`, `reducedMotion()`, `mediaType()`, `userAgent()`, `timezone()`, `locale()`, `geolocation()`
  - Network: `headers()`, `cookies()`, `authBasic()`, `authBearer()`, `bypassCsp()`
  - Cache: `cacheTtl()`, `cacheRefresh()`
  - PDF: 14 methods for PDF customization
  - Storage: `storageEnabled()`, `storagePath()`, `storageAcl()`
- `CacheManager` for cache operations
  - `get()` - Get cached screenshot
  - `delete()` - Delete cache entry
  - `purge()` - Bulk purge by keys
  - `purgeUrl()` - Purge by URL pattern
  - `purgeBefore()` - Purge by date
  - `purgePattern()` - Purge by storage path pattern
- `Webhook` static helper class
  - `verify()` - HMAC-SHA256 signature verification
  - `parse()` - Parse webhook payload
  - `extractHeaders()` - Extract headers from various frameworks
- `RenderScreenshotException` with structured error handling
  - Factory methods for common errors
  - Retry information for rate limits

[1.0.0]: https://github.com/renderscreenshot/rs-php/releases/tag/v1.0.0
