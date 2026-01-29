<?php

declare(strict_types=1);

namespace RenderScreenshot\Tests;

use PHPUnit\Framework\TestCase;
use RenderScreenshot\TakeOptions;

class TakeOptionsTest extends TestCase
{
    public function testUrlFactory(): void
    {
        $options = TakeOptions::url('https://example.com');
        $config = $options->toConfig();

        $this->assertSame('https://example.com', $config['url']);
    }

    public function testHtmlFactory(): void
    {
        $options = TakeOptions::html('<h1>Hello</h1>');
        $config = $options->toConfig();

        $this->assertSame('<h1>Hello</h1>', $config['html']);
    }

    public function testFromConfigFactory(): void
    {
        $config = ['url' => 'https://example.com', 'width' => 1200];
        $options = TakeOptions::fromConfig($config);

        $this->assertSame($config, $options->toConfig());
    }

    public function testImmutability(): void
    {
        $options1 = TakeOptions::url('https://example.com');
        $options2 = $options1->width(1200);

        $this->assertNotSame($options1, $options2);
        $this->assertArrayNotHasKey('width', $options1->toConfig());
        $this->assertSame(1200, $options2->toConfig()['width']);
    }

    public function testViewportMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->width(1200)
            ->height(630)
            ->scale(2.0)
            ->mobile(true);

        $config = $options->toConfig();

        $this->assertSame(1200, $config['width']);
        $this->assertSame(630, $config['height']);
        $this->assertSame(2.0, $config['scale']);
        $this->assertTrue($config['mobile']);
    }

    public function testCaptureMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->fullPage(true)
            ->element('.content')
            ->format('png')
            ->quality(90);

        $config = $options->toConfig();

        $this->assertTrue($config['full_page']);
        $this->assertSame('.content', $config['element']);
        $this->assertSame('png', $config['format']);
        $this->assertSame(90, $config['quality']);
    }

    public function testWaitMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->waitFor('networkidle')
            ->delay(2000)
            ->waitForSelector('.loaded')
            ->waitForTimeout(30000);

        $config = $options->toConfig();

        $this->assertSame('networkidle', $config['wait_for']);
        $this->assertSame(2000, $config['delay']);
        $this->assertSame('.loaded', $config['wait_for_selector']);
        $this->assertSame(30000, $config['wait_for_timeout']);
    }

    public function testPresetMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->preset('og_card')
            ->device('iphone_14_pro');

        $config = $options->toConfig();

        $this->assertSame('og_card', $config['preset']);
        $this->assertSame('iphone_14_pro', $config['device']);
    }

    public function testBlockingMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->blockAds(true)
            ->blockTrackers(true)
            ->blockCookieBanners(true)
            ->blockChatWidgets(true)
            ->blockUrls(['*.ads.com/*'])
            ->blockResources(['font', 'media']);

        $config = $options->toConfig();

        $this->assertTrue($config['block_ads']);
        $this->assertTrue($config['block_trackers']);
        $this->assertTrue($config['block_cookie_banners']);
        $this->assertTrue($config['block_chat_widgets']);
        $this->assertSame(['*.ads.com/*'], $config['block_urls']);
        $this->assertSame(['font', 'media'], $config['block_resources']);
    }

    public function testPageManipulationMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->injectScript('console.log("test");')
            ->injectStyle('body { background: red; }')
            ->click('.button')
            ->hide(['.popup'])
            ->remove(['.banner']);

        $config = $options->toConfig();

        $this->assertSame('console.log("test");', $config['inject_script']);
        $this->assertSame('body { background: red; }', $config['inject_style']);
        $this->assertSame('.button', $config['click']);
        $this->assertSame(['.popup'], $config['hide']);
        $this->assertSame(['.banner'], $config['remove']);
    }

    public function testBrowserEmulationMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->darkMode(true)
            ->reducedMotion(true)
            ->mediaType('print')
            ->userAgent('Custom UA')
            ->timezone('America/New_York')
            ->locale('en-US')
            ->geolocation(40.7128, -74.006, 100.0);

        $config = $options->toConfig();

        $this->assertTrue($config['dark_mode']);
        $this->assertTrue($config['reduced_motion']);
        $this->assertSame('print', $config['media_type']);
        $this->assertSame('Custom UA', $config['user_agent']);
        $this->assertSame('America/New_York', $config['timezone']);
        $this->assertSame('en-US', $config['locale']);
        $this->assertSame([
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'accuracy' => 100.0,
        ], $config['geolocation']);
    }

    public function testGeolocationWithoutAccuracy(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->geolocation(40.7128, -74.006);

        $config = $options->toConfig();

        $this->assertSame([
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ], $config['geolocation']);
    }

    public function testNetworkMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->headers(['X-Custom' => 'value'])
            ->cookies([['name' => 'session', 'value' => 'abc', 'domain' => 'example.com']])
            ->authBasic('user', 'pass')
            ->authBearer('token123')
            ->bypassCsp(true);

        $config = $options->toConfig();

        $this->assertSame(['X-Custom' => 'value'], $config['headers']);
        $this->assertSame([['name' => 'session', 'value' => 'abc', 'domain' => 'example.com']], $config['cookies']);
        $this->assertSame(['username' => 'user', 'password' => 'pass'], $config['auth_basic']);
        $this->assertSame('token123', $config['auth_bearer']);
        $this->assertTrue($config['bypass_csp']);
    }

    public function testCacheMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->cacheTtl(86400)
            ->cacheRefresh(true);

        $config = $options->toConfig();

        $this->assertSame(86400, $config['cache_ttl']);
        $this->assertTrue($config['cache_refresh']);
    }

    public function testPdfMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->format('pdf')
            ->pdfPaperSize('a4')
            ->pdfWidth('8.5in')
            ->pdfHeight('11in')
            ->pdfLandscape(true)
            ->pdfMargin('1in')
            ->pdfMarginTop('0.5in')
            ->pdfMarginRight('0.5in')
            ->pdfMarginBottom('0.5in')
            ->pdfMarginLeft('0.5in')
            ->pdfScale(0.8)
            ->pdfPrintBackground(true)
            ->pdfPageRanges('1-5')
            ->pdfHeader('<div>Header</div>')
            ->pdfFooter('<div>Footer</div>')
            ->pdfFitOnePage(true)
            ->pdfPreferCssPageSize(true);

        $config = $options->toConfig();

        $this->assertSame('pdf', $config['format']);
        $this->assertSame('a4', $config['pdf_paper_size']);
        $this->assertSame('8.5in', $config['pdf_width']);
        $this->assertSame('11in', $config['pdf_height']);
        $this->assertTrue($config['pdf_landscape']);
        $this->assertSame('1in', $config['pdf_margin']);
        $this->assertSame('0.5in', $config['pdf_margin_top']);
        $this->assertSame('0.5in', $config['pdf_margin_right']);
        $this->assertSame('0.5in', $config['pdf_margin_bottom']);
        $this->assertSame('0.5in', $config['pdf_margin_left']);
        $this->assertSame(0.8, $config['pdf_scale']);
        $this->assertTrue($config['pdf_print_background']);
        $this->assertSame('1-5', $config['pdf_page_ranges']);
        $this->assertSame('<div>Header</div>', $config['pdf_header']);
        $this->assertSame('<div>Footer</div>', $config['pdf_footer']);
        $this->assertTrue($config['pdf_fit_one_page']);
        $this->assertTrue($config['pdf_prefer_css_page_size']);
    }

    public function testStorageMethods(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->storageEnabled(true)
            ->storagePath('{year}/{month}/{hash}.{ext}')
            ->storageAcl('public-read');

        $config = $options->toConfig();

        $this->assertTrue($config['storage_enabled']);
        $this->assertSame('{year}/{month}/{hash}.{ext}', $config['storage_path']);
        $this->assertSame('public-read', $config['storage_acl']);
    }

    public function testToParams(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->width(1200)
            ->height(630)
            ->preset('og_card')
            ->blockAds(true)
            ->format('pdf')
            ->pdfPaperSize('a4')
            ->pdfLandscape(true)
            ->storageEnabled(true)
            ->storagePath('screenshots/{hash}.{ext}');

        $params = $options->toParams();

        $this->assertSame('https://example.com', $params['url']);
        $this->assertSame(['width' => 1200, 'height' => 630], $params['viewport']);
        $this->assertSame('og_card', $params['preset']);
        $this->assertTrue($params['block_ads']);
        $this->assertSame('pdf', $params['format']);
        $this->assertSame(['paper_size' => 'a4', 'landscape' => true], $params['pdf']);
        $this->assertSame(['enabled' => true, 'path' => 'screenshots/{hash}.{ext}'], $params['storage']);
    }

    public function testToQueryString(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->width(1200)
            ->height(630)
            ->preset('og_card')
            ->blockAds(true)
            ->darkMode(true)
            ->cacheTtl(3600);

        $query = $options->toQueryString();

        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com', $query);
        $this->assertStringContainsString('width=1200', $query);
        $this->assertStringContainsString('height=630', $query);
        $this->assertStringContainsString('preset=og_card', $query);
        $this->assertStringContainsString('block_ads=true', $query);
        $this->assertStringContainsString('dark_mode=true', $query);
        $this->assertStringContainsString('cache_ttl=3600', $query);
    }

    public function testChainedMethodCalls(): void
    {
        $options = TakeOptions::url('https://example.com')
            ->preset('og_card')
            ->blockAds()
            ->blockTrackers()
            ->blockCookieBanners()
            ->darkMode();

        $config = $options->toConfig();

        $this->assertSame('https://example.com', $config['url']);
        $this->assertSame('og_card', $config['preset']);
        $this->assertTrue($config['block_ads']);
        $this->assertTrue($config['block_trackers']);
        $this->assertTrue($config['block_cookie_banners']);
        $this->assertTrue($config['dark_mode']);
    }
}
