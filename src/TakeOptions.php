<?php

declare(strict_types=1);

namespace RenderScreenshot;

/**
 * Fluent builder for screenshot options.
 *
 * Use the static methods `url()` or `html()` to create a new instance,
 * then chain method calls to configure the screenshot options.
 *
 * @example
 * $options = TakeOptions::url('https://example.com')
 *     ->width(1200)
 *     ->height(630)
 *     ->format('png')
 *     ->blockAds();
 *
 * $image = $client->take($options);
 */
class TakeOptions
{
    /**
     * Configuration dictionary.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a new TakeOptions instance.
     *
     * @param array<string, mixed>|null $config Initial configuration dictionary (optional)
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [];
    }

    /**
     * Create options with a URL target.
     *
     * @param string $url The URL to capture
     *
     * @return self A new TakeOptions instance
     *
     * @example
     * $options = TakeOptions::url('https://example.com');
     */
    public static function url(string $url): self
    {
        return new self(['url' => $url]);
    }

    /**
     * Create options with HTML content.
     *
     * @param string $html The HTML content to render
     *
     * @return self A new TakeOptions instance
     *
     * @example
     * $options = TakeOptions::html('<h1>Hello World</h1>');
     */
    public static function html(string $html): self
    {
        return new self(['html' => $html]);
    }

    /**
     * Create options from an existing config array.
     *
     * @param array<string, mixed> $config Configuration array
     *
     * @return self A new TakeOptions instance
     */
    public static function fromConfig(array $config): self
    {
        return new self($config);
    }

    /**
     * Create a copy with updated values (immutable pattern).
     *
     * @param array<string, mixed> $updates Values to update
     *
     * @return self A new TakeOptions instance
     */
    private function copyWith(array $updates): self
    {
        return new self(array_merge($this->config, $updates));
    }

    // --- Viewport ---

    /**
     * Set viewport width in pixels.
     *
     * @param int $value Width in pixels (e.g., 1200)
     *
     * @return self A new TakeOptions instance
     */
    public function width(int $value): self
    {
        return $this->copyWith(['width' => $value]);
    }

    /**
     * Set viewport height in pixels.
     *
     * @param int $value Height in pixels (e.g., 630)
     *
     * @return self A new TakeOptions instance
     */
    public function height(int $value): self
    {
        return $this->copyWith(['height' => $value]);
    }

    /**
     * Set device scale factor (1-3).
     *
     * @param float $value Scale factor (e.g., 2 for retina)
     *
     * @return self A new TakeOptions instance
     */
    public function scale(float $value): self
    {
        return $this->copyWith(['scale' => $value]);
    }

    /**
     * Enable mobile emulation.
     *
     * @param bool $value True to enable mobile mode
     *
     * @return self A new TakeOptions instance
     */
    public function mobile(bool $value = true): self
    {
        return $this->copyWith(['mobile' => $value]);
    }

    // --- Capture ---

    /**
     * Capture full scrollable page.
     *
     * @param bool $value True to capture full page
     *
     * @return self A new TakeOptions instance
     */
    public function fullPage(bool $value = true): self
    {
        return $this->copyWith(['full_page' => $value]);
    }

    /**
     * Capture specific element by CSS selector.
     *
     * @param string $selector CSS selector (e.g., "#main", ".content")
     *
     * @return self A new TakeOptions instance
     */
    public function element(string $selector): self
    {
        return $this->copyWith(['element' => $selector]);
    }

    /**
     * Set output format.
     *
     * @param string $value Image format ("png", "jpeg", "webp", "pdf")
     *
     * @return self A new TakeOptions instance
     */
    public function format(string $value): self
    {
        return $this->copyWith(['format' => $value]);
    }

    /**
     * Set JPEG/WebP quality (1-100).
     *
     * @param int $value Quality percentage
     *
     * @return self A new TakeOptions instance
     */
    public function quality(int $value): self
    {
        return $this->copyWith(['quality' => $value]);
    }

    // --- Wait ---

    /**
     * Set wait condition.
     *
     * @param string $value Wait condition ("load", "networkidle", "domcontentloaded")
     *
     * @return self A new TakeOptions instance
     */
    public function waitFor(string $value): self
    {
        return $this->copyWith(['wait_for' => $value]);
    }

    /**
     * Add delay after page load (milliseconds).
     *
     * @param int $value Delay in milliseconds
     *
     * @return self A new TakeOptions instance
     */
    public function delay(int $value): self
    {
        return $this->copyWith(['delay' => $value]);
    }

    /**
     * Wait for CSS selector to appear.
     *
     * @param string $selector CSS selector to wait for
     *
     * @return self A new TakeOptions instance
     */
    public function waitForSelector(string $selector): self
    {
        return $this->copyWith(['wait_for_selector' => $selector]);
    }

    /**
     * Maximum wait time in milliseconds.
     *
     * @param int $value Timeout in milliseconds
     *
     * @return self A new TakeOptions instance
     */
    public function waitForTimeout(int $value): self
    {
        return $this->copyWith(['wait_for_timeout' => $value]);
    }

    // --- Presets ---

    /**
     * Use a preset configuration.
     *
     * @param string $value Preset identifier (e.g., "og_card", "twitter_card")
     *
     * @return self A new TakeOptions instance
     */
    public function preset(string $value): self
    {
        return $this->copyWith(['preset' => $value]);
    }

    /**
     * Emulate a specific device.
     *
     * @param string $value Device identifier (e.g., "iphone_14_pro", "pixel_7")
     *
     * @return self A new TakeOptions instance
     */
    public function device(string $value): self
    {
        return $this->copyWith(['device' => $value]);
    }

    // --- Blocking ---

    /**
     * Block ad network domains.
     *
     * @param bool $value True to block ads
     *
     * @return self A new TakeOptions instance
     */
    public function blockAds(bool $value = true): self
    {
        return $this->copyWith(['block_ads' => $value]);
    }

    /**
     * Block analytics/tracking.
     *
     * @param bool $value True to block trackers
     *
     * @return self A new TakeOptions instance
     */
    public function blockTrackers(bool $value = true): self
    {
        return $this->copyWith(['block_trackers' => $value]);
    }

    /**
     * Auto-dismiss cookie popups.
     *
     * @param bool $value True to block cookie banners
     *
     * @return self A new TakeOptions instance
     */
    public function blockCookieBanners(bool $value = true): self
    {
        return $this->copyWith(['block_cookie_banners' => $value]);
    }

    /**
     * Block chat widgets.
     *
     * @param bool $value True to block chat widgets
     *
     * @return self A new TakeOptions instance
     */
    public function blockChatWidgets(bool $value = true): self
    {
        return $this->copyWith(['block_chat_widgets' => $value]);
    }

    /**
     * Block URLs matching patterns (glob).
     *
     * @param array<string> $patterns List of glob patterns to block
     *
     * @return self A new TakeOptions instance
     */
    public function blockUrls(array $patterns): self
    {
        return $this->copyWith(['block_urls' => $patterns]);
    }

    /**
     * Block specific resource types.
     *
     * @param array<string> $types List of resource types to block ("font", "media", "image", "script", "stylesheet")
     *
     * @return self A new TakeOptions instance
     */
    public function blockResources(array $types): self
    {
        return $this->copyWith(['block_resources' => $types]);
    }

    // --- Page manipulation ---

    /**
     * Inject JavaScript (inline or URL).
     *
     * @param string $script JavaScript code or URL to a script file
     *
     * @return self A new TakeOptions instance
     */
    public function injectScript(string $script): self
    {
        return $this->copyWith(['inject_script' => $script]);
    }

    /**
     * Inject CSS (inline or URL).
     *
     * @param string $style CSS code or URL to a stylesheet
     *
     * @return self A new TakeOptions instance
     */
    public function injectStyle(string $style): self
    {
        return $this->copyWith(['inject_style' => $style]);
    }

    /**
     * Click element before capture.
     *
     * @param string $selector CSS selector of element to click
     *
     * @return self A new TakeOptions instance
     */
    public function click(string $selector): self
    {
        return $this->copyWith(['click' => $selector]);
    }

    /**
     * Hide elements (visibility: hidden).
     *
     * @param array<string> $selectors List of CSS selectors to hide
     *
     * @return self A new TakeOptions instance
     */
    public function hide(array $selectors): self
    {
        return $this->copyWith(['hide' => $selectors]);
    }

    /**
     * Remove elements from DOM.
     *
     * @param array<string> $selectors List of CSS selectors to remove
     *
     * @return self A new TakeOptions instance
     */
    public function remove(array $selectors): self
    {
        return $this->copyWith(['remove' => $selectors]);
    }

    // --- Browser emulation ---

    /**
     * Enable dark mode (prefers-color-scheme: dark).
     *
     * @param bool $value True to enable dark mode
     *
     * @return self A new TakeOptions instance
     */
    public function darkMode(bool $value = true): self
    {
        return $this->copyWith(['dark_mode' => $value]);
    }

    /**
     * Enable reduced motion preference.
     *
     * @param bool $value True to enable reduced motion
     *
     * @return self A new TakeOptions instance
     */
    public function reducedMotion(bool $value = true): self
    {
        return $this->copyWith(['reduced_motion' => $value]);
    }

    /**
     * Set media type emulation.
     *
     * @param string $value Media type ("screen" or "print")
     *
     * @return self A new TakeOptions instance
     */
    public function mediaType(string $value): self
    {
        return $this->copyWith(['media_type' => $value]);
    }

    /**
     * Set custom user agent.
     *
     * @param string $value User agent string
     *
     * @return self A new TakeOptions instance
     */
    public function userAgent(string $value): self
    {
        return $this->copyWith(['user_agent' => $value]);
    }

    /**
     * Set timezone (IANA format).
     *
     * @param string $value Timezone identifier (e.g., "America/New_York")
     *
     * @return self A new TakeOptions instance
     */
    public function timezone(string $value): self
    {
        return $this->copyWith(['timezone' => $value]);
    }

    /**
     * Set locale (BCP 47 format).
     *
     * @param string $value Locale identifier (e.g., "en-US", "fr-FR")
     *
     * @return self A new TakeOptions instance
     */
    public function locale(string $value): self
    {
        return $this->copyWith(['locale' => $value]);
    }

    /**
     * Set geolocation coordinates.
     *
     * @param float      $latitude  Latitude coordinate
     * @param float      $longitude Longitude coordinate
     * @param float|null $accuracy  Accuracy in meters (optional)
     *
     * @return self A new TakeOptions instance
     */
    public function geolocation(float $latitude, float $longitude, ?float $accuracy = null): self
    {
        $geo = ['latitude' => $latitude, 'longitude' => $longitude];
        if ($accuracy !== null) {
            $geo['accuracy'] = $accuracy;
        }

        return $this->copyWith(['geolocation' => $geo]);
    }

    // --- Network ---

    /**
     * Set custom HTTP headers.
     *
     * @param array<string, string> $value Dictionary of header name-value pairs
     *
     * @return self A new TakeOptions instance
     */
    public function headers(array $value): self
    {
        return $this->copyWith(['headers' => $value]);
    }

    /**
     * Set cookies.
     *
     * @param array<array<string, mixed>> $value List of cookie arrays
     *
     * @return self A new TakeOptions instance
     */
    public function cookies(array $value): self
    {
        return $this->copyWith(['cookies' => $value]);
    }

    /**
     * Set HTTP Basic authentication.
     *
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     *
     * @return self A new TakeOptions instance
     */
    public function authBasic(string $username, string $password): self
    {
        return $this->copyWith(['auth_basic' => ['username' => $username, 'password' => $password]]);
    }

    /**
     * Set Bearer token authentication.
     *
     * @param string $token Bearer token
     *
     * @return self A new TakeOptions instance
     */
    public function authBearer(string $token): self
    {
        return $this->copyWith(['auth_bearer' => $token]);
    }

    /**
     * Bypass Content Security Policy.
     *
     * @param bool $value True to bypass CSP
     *
     * @return self A new TakeOptions instance
     */
    public function bypassCsp(bool $value = true): self
    {
        return $this->copyWith(['bypass_csp' => $value]);
    }

    // --- Cache ---

    /**
     * Set cache TTL in seconds (3600-2592000).
     *
     * @param int $value TTL in seconds
     *
     * @return self A new TakeOptions instance
     */
    public function cacheTtl(int $value): self
    {
        return $this->copyWith(['cache_ttl' => $value]);
    }

    /**
     * Force cache refresh.
     *
     * @param bool $value True to force refresh
     *
     * @return self A new TakeOptions instance
     */
    public function cacheRefresh(bool $value = true): self
    {
        return $this->copyWith(['cache_refresh' => $value]);
    }

    // --- PDF options ---

    /**
     * Set PDF paper size.
     *
     * @param string $value Paper size (e.g., "a4", "letter")
     *
     * @return self A new TakeOptions instance
     */
    public function pdfPaperSize(string $value): self
    {
        return $this->copyWith(['pdf_paper_size' => $value]);
    }

    /**
     * Set custom PDF width (CSS units).
     *
     * @param string $value Width string (e.g., "8.5in")
     *
     * @return self A new TakeOptions instance
     */
    public function pdfWidth(string $value): self
    {
        return $this->copyWith(['pdf_width' => $value]);
    }

    /**
     * Set custom PDF height (CSS units).
     *
     * @param string $value Height string (e.g., "11in")
     *
     * @return self A new TakeOptions instance
     */
    public function pdfHeight(string $value): self
    {
        return $this->copyWith(['pdf_height' => $value]);
    }

    /**
     * Set PDF landscape orientation.
     *
     * @param bool $value True for landscape
     *
     * @return self A new TakeOptions instance
     */
    public function pdfLandscape(bool $value = true): self
    {
        return $this->copyWith(['pdf_landscape' => $value]);
    }

    /**
     * Set uniform PDF margin (CSS units).
     *
     * @param string $value Margin string (e.g., "1in", "20mm")
     *
     * @return self A new TakeOptions instance
     */
    public function pdfMargin(string $value): self
    {
        return $this->copyWith(['pdf_margin' => $value]);
    }

    /**
     * Set PDF top margin.
     *
     * @param string $value Top margin string
     *
     * @return self A new TakeOptions instance
     */
    public function pdfMarginTop(string $value): self
    {
        return $this->copyWith(['pdf_margin_top' => $value]);
    }

    /**
     * Set PDF right margin.
     *
     * @param string $value Right margin string
     *
     * @return self A new TakeOptions instance
     */
    public function pdfMarginRight(string $value): self
    {
        return $this->copyWith(['pdf_margin_right' => $value]);
    }

    /**
     * Set PDF bottom margin.
     *
     * @param string $value Bottom margin string
     *
     * @return self A new TakeOptions instance
     */
    public function pdfMarginBottom(string $value): self
    {
        return $this->copyWith(['pdf_margin_bottom' => $value]);
    }

    /**
     * Set PDF left margin.
     *
     * @param string $value Left margin string
     *
     * @return self A new TakeOptions instance
     */
    public function pdfMarginLeft(string $value): self
    {
        return $this->copyWith(['pdf_margin_left' => $value]);
    }

    /**
     * Set PDF scale factor (0.1-2.0).
     *
     * @param float $value Scale factor
     *
     * @return self A new TakeOptions instance
     */
    public function pdfScale(float $value): self
    {
        return $this->copyWith(['pdf_scale' => $value]);
    }

    /**
     * Include background graphics in PDF.
     *
     * @param bool $value True to include backgrounds
     *
     * @return self A new TakeOptions instance
     */
    public function pdfPrintBackground(bool $value = true): self
    {
        return $this->copyWith(['pdf_print_background' => $value]);
    }

    /**
     * Set PDF page ranges (e.g., "1-5, 8").
     *
     * @param string $value Page ranges string
     *
     * @return self A new TakeOptions instance
     */
    public function pdfPageRanges(string $value): self
    {
        return $this->copyWith(['pdf_page_ranges' => $value]);
    }

    /**
     * Set PDF header HTML template.
     *
     * @param string $value HTML template for header
     *
     * @return self A new TakeOptions instance
     */
    public function pdfHeader(string $value): self
    {
        return $this->copyWith(['pdf_header' => $value]);
    }

    /**
     * Set PDF footer HTML template.
     *
     * @param string $value HTML template for footer
     *
     * @return self A new TakeOptions instance
     */
    public function pdfFooter(string $value): self
    {
        return $this->copyWith(['pdf_footer' => $value]);
    }

    /**
     * Fit content to single PDF page.
     *
     * @param bool $value True to fit to one page
     *
     * @return self A new TakeOptions instance
     */
    public function pdfFitOnePage(bool $value = true): self
    {
        return $this->copyWith(['pdf_fit_one_page' => $value]);
    }

    /**
     * Use CSS-defined page size for PDF.
     *
     * @param bool $value True to use CSS page size
     *
     * @return self A new TakeOptions instance
     */
    public function pdfPreferCssPageSize(bool $value = true): self
    {
        return $this->copyWith(['pdf_prefer_css_page_size' => $value]);
    }

    // --- Storage (BYOS) ---

    /**
     * Enable custom storage upload.
     *
     * @param bool $value True to enable storage
     *
     * @return self A new TakeOptions instance
     */
    public function storageEnabled(bool $value = true): self
    {
        return $this->copyWith(['storage_enabled' => $value]);
    }

    /**
     * Set storage path template.
     *
     * Supports variables: {hash}, {ext}, {year}, {month}, {day},
     * {timestamp}, {domain}, {uuid}
     *
     * @param string $value Path template string
     *
     * @return self A new TakeOptions instance
     */
    public function storagePath(string $value): self
    {
        return $this->copyWith(['storage_path' => $value]);
    }

    /**
     * Set storage ACL.
     *
     * @param string $value ACL setting ("public-read" or "private")
     *
     * @return self A new TakeOptions instance
     */
    public function storageAcl(string $value): self
    {
        return $this->copyWith(['storage_acl' => $value]);
    }

    // --- Output ---

    /**
     * Get the raw configuration array.
     *
     * @return array<string, mixed> Copy of the configuration array
     */
    public function toConfig(): array
    {
        return $this->config;
    }

    /**
     * Convert to API request parameters (for POST body).
     *
     * This method converts the configuration to the nested format
     * expected by the API.
     *
     * @return array<string, mixed> Array suitable for JSON POST body
     */
    public function toParams(): array
    {
        $params = [];
        $config = $this->config;

        // Target
        if (isset($config['url'])) {
            $params['url'] = $config['url'];
        }
        if (isset($config['html'])) {
            $params['html'] = $config['html'];
        }

        // Viewport (nested)
        $viewport = [];
        if (isset($config['width'])) {
            $viewport['width'] = $config['width'];
        }
        if (isset($config['height'])) {
            $viewport['height'] = $config['height'];
        }
        if (isset($config['scale'])) {
            $viewport['scale'] = $config['scale'];
        }
        if (isset($config['mobile'])) {
            $viewport['mobile'] = $config['mobile'];
        }
        if (!empty($viewport)) {
            $params['viewport'] = $viewport;
        }

        // Capture
        if (isset($config['full_page'])) {
            $params['full_page'] = $config['full_page'];
        }
        if (isset($config['element'])) {
            $params['element'] = $config['element'];
        }
        if (isset($config['format'])) {
            $params['format'] = $config['format'];
        }
        if (isset($config['quality'])) {
            $params['quality'] = $config['quality'];
        }

        // Wait
        if (isset($config['wait_for'])) {
            $params['wait_for'] = $config['wait_for'];
        }
        if (isset($config['delay'])) {
            $params['delay'] = $config['delay'];
        }
        if (isset($config['wait_for_selector'])) {
            $params['wait_for_selector'] = $config['wait_for_selector'];
        }
        if (isset($config['wait_for_timeout'])) {
            $params['wait_for_timeout'] = $config['wait_for_timeout'];
        }

        // Presets
        if (isset($config['preset'])) {
            $params['preset'] = $config['preset'];
        }
        if (isset($config['device'])) {
            $params['device'] = $config['device'];
        }

        // Blocking
        if (isset($config['block_ads'])) {
            $params['block_ads'] = $config['block_ads'];
        }
        if (isset($config['block_trackers'])) {
            $params['block_trackers'] = $config['block_trackers'];
        }
        if (isset($config['block_cookie_banners'])) {
            $params['block_cookie_banners'] = $config['block_cookie_banners'];
        }
        if (isset($config['block_chat_widgets'])) {
            $params['block_chat_widgets'] = $config['block_chat_widgets'];
        }
        if (isset($config['block_urls'])) {
            $params['block_urls'] = $config['block_urls'];
        }
        if (isset($config['block_resources'])) {
            $params['block_resources'] = $config['block_resources'];
        }

        // Page manipulation
        if (isset($config['inject_script'])) {
            $params['inject_script'] = $config['inject_script'];
        }
        if (isset($config['inject_style'])) {
            $params['inject_style'] = $config['inject_style'];
        }
        if (isset($config['click'])) {
            $params['click'] = $config['click'];
        }
        if (isset($config['hide'])) {
            $params['hide'] = $config['hide'];
        }
        if (isset($config['remove'])) {
            $params['remove'] = $config['remove'];
        }

        // Browser emulation
        if (isset($config['dark_mode'])) {
            $params['dark_mode'] = $config['dark_mode'];
        }
        if (isset($config['reduced_motion'])) {
            $params['reduced_motion'] = $config['reduced_motion'];
        }
        if (isset($config['media_type'])) {
            $params['media_type'] = $config['media_type'];
        }
        if (isset($config['user_agent'])) {
            $params['user_agent'] = $config['user_agent'];
        }
        if (isset($config['timezone'])) {
            $params['timezone'] = $config['timezone'];
        }
        if (isset($config['locale'])) {
            $params['locale'] = $config['locale'];
        }
        if (isset($config['geolocation'])) {
            $params['geolocation'] = $config['geolocation'];
        }

        // Network
        if (isset($config['headers'])) {
            $params['headers'] = $config['headers'];
        }
        if (isset($config['cookies'])) {
            $params['cookies'] = $config['cookies'];
        }
        if (isset($config['auth_basic'])) {
            $params['auth_basic'] = $config['auth_basic'];
        }
        if (isset($config['auth_bearer'])) {
            $params['auth_bearer'] = $config['auth_bearer'];
        }
        if (isset($config['bypass_csp'])) {
            $params['bypass_csp'] = $config['bypass_csp'];
        }

        // Cache
        if (isset($config['cache_ttl'])) {
            $params['cache_ttl'] = $config['cache_ttl'];
        }
        if (isset($config['cache_refresh'])) {
            $params['cache_refresh'] = $config['cache_refresh'];
        }

        // PDF (nested)
        $pdf = [];
        if (isset($config['pdf_paper_size'])) {
            $pdf['paper_size'] = $config['pdf_paper_size'];
        }
        if (isset($config['pdf_width'])) {
            $pdf['width'] = $config['pdf_width'];
        }
        if (isset($config['pdf_height'])) {
            $pdf['height'] = $config['pdf_height'];
        }
        if (isset($config['pdf_landscape'])) {
            $pdf['landscape'] = $config['pdf_landscape'];
        }
        if (isset($config['pdf_margin'])) {
            $pdf['margin'] = $config['pdf_margin'];
        }
        if (isset($config['pdf_margin_top'])) {
            $pdf['margin_top'] = $config['pdf_margin_top'];
        }
        if (isset($config['pdf_margin_right'])) {
            $pdf['margin_right'] = $config['pdf_margin_right'];
        }
        if (isset($config['pdf_margin_bottom'])) {
            $pdf['margin_bottom'] = $config['pdf_margin_bottom'];
        }
        if (isset($config['pdf_margin_left'])) {
            $pdf['margin_left'] = $config['pdf_margin_left'];
        }
        if (isset($config['pdf_scale'])) {
            $pdf['scale'] = $config['pdf_scale'];
        }
        if (isset($config['pdf_print_background'])) {
            $pdf['print_background'] = $config['pdf_print_background'];
        }
        if (isset($config['pdf_page_ranges'])) {
            $pdf['page_ranges'] = $config['pdf_page_ranges'];
        }
        if (isset($config['pdf_header'])) {
            $pdf['header'] = $config['pdf_header'];
        }
        if (isset($config['pdf_footer'])) {
            $pdf['footer'] = $config['pdf_footer'];
        }
        if (isset($config['pdf_fit_one_page'])) {
            $pdf['fit_one_page'] = $config['pdf_fit_one_page'];
        }
        if (isset($config['pdf_prefer_css_page_size'])) {
            $pdf['prefer_css_page_size'] = $config['pdf_prefer_css_page_size'];
        }
        if (!empty($pdf)) {
            $params['pdf'] = $pdf;
        }

        // Storage (nested)
        $storage = [];
        if (isset($config['storage_enabled'])) {
            $storage['enabled'] = $config['storage_enabled'];
        }
        if (isset($config['storage_path'])) {
            $storage['path'] = $config['storage_path'];
        }
        if (isset($config['storage_acl'])) {
            $storage['acl'] = $config['storage_acl'];
        }
        if (!empty($storage)) {
            $params['storage'] = $storage;
        }

        return $params;
    }

    /**
     * Convert to URL query string (for GET requests).
     *
     * This method converts the configuration to flat query parameters
     * suitable for GET requests.
     *
     * @return string URL-encoded query string
     */
    public function toQueryString(): string
    {
        $params = [];
        $config = $this->config;

        // Target
        if (isset($config['url'])) {
            $params['url'] = $config['url'];
        }

        // Viewport (flat for GET)
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

        // Capture
        if (isset($config['full_page'])) {
            $params['full_page'] = $config['full_page'] ? 'true' : 'false';
        }
        if (isset($config['element'])) {
            $params['element'] = $config['element'];
        }
        if (isset($config['format'])) {
            $params['format'] = $config['format'];
        }
        if (isset($config['quality'])) {
            $params['quality'] = (string) $config['quality'];
        }

        // Wait
        if (isset($config['wait_for'])) {
            $params['wait_for'] = $config['wait_for'];
        }
        if (isset($config['delay'])) {
            $params['delay'] = (string) $config['delay'];
        }
        if (isset($config['wait_for_selector'])) {
            $params['wait_for_selector'] = $config['wait_for_selector'];
        }
        if (isset($config['wait_for_timeout'])) {
            $params['wait_for_timeout'] = (string) $config['wait_for_timeout'];
        }

        // Presets
        if (isset($config['preset'])) {
            $params['preset'] = $config['preset'];
        }
        if (isset($config['device'])) {
            $params['device'] = $config['device'];
        }

        // Blocking
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

        // Browser emulation
        if (isset($config['dark_mode'])) {
            $params['dark_mode'] = $config['dark_mode'] ? 'true' : 'false';
        }
        if (isset($config['reduced_motion'])) {
            $params['reduced_motion'] = $config['reduced_motion'] ? 'true' : 'false';
        }
        if (isset($config['media_type'])) {
            $params['media_type'] = $config['media_type'];
        }
        if (isset($config['user_agent'])) {
            $params['user_agent'] = $config['user_agent'];
        }
        if (isset($config['timezone'])) {
            $params['timezone'] = $config['timezone'];
        }
        if (isset($config['locale'])) {
            $params['locale'] = $config['locale'];
        }

        // Cache
        if (isset($config['cache_ttl'])) {
            $params['cache_ttl'] = (string) $config['cache_ttl'];
        }
        if (isset($config['cache_refresh'])) {
            $params['cache_refresh'] = $config['cache_refresh'] ? 'true' : 'false';
        }

        return http_build_query($params);
    }
}
