<?php

namespace Tests\Unit;

use App\Services\PlatformDetector;
use PHPUnit\Framework\TestCase;

class PlatformDetectorTest extends TestCase
{
    private PlatformDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PlatformDetector();
    }

    public function test_detects_iphone(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        );

        $this->assertEquals('ios', $result['platform']);
        $this->assertEquals('mobile', $result['device_type']);
        $this->assertEquals('17.0', $result['os_version']);
        $this->assertEquals('Safari', $result['browser']);
    }

    public function test_detects_ipad(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/604.1'
        );

        $this->assertEquals('ios', $result['platform']);
        $this->assertEquals('tablet', $result['device_type']);
    }

    public function test_detects_android_phone(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        );

        $this->assertEquals('android', $result['platform']);
        $this->assertEquals('mobile', $result['device_type']);
        $this->assertEquals('14', $result['os_version']);
        $this->assertEquals('Chrome', $result['browser']);
    }

    public function test_detects_desktop_windows(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertEquals('desktop', $result['platform']);
        $this->assertEquals('desktop', $result['device_type']);
        $this->assertEquals('Chrome', $result['browser']);
    }

    public function test_detects_firefox_browser(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0'
        );

        $this->assertEquals('desktop', $result['platform']);
        $this->assertEquals('Firefox', $result['browser']);
    }

    public function test_detects_edge_browser(): void
    {
        $result = $this->detector->detect(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        );

        $this->assertEquals('Edge', $result['browser']);
    }
}
