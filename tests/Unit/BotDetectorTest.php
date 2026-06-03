<?php

namespace Tests\Unit;

use App\Services\BotDetector;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    private BotDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BotDetector();
    }

    public function test_detects_googlebot(): void
    {
        $this->assertTrue(
            $this->detector->isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
        );
    }

    public function test_detects_facebookbot(): void
    {
        $this->assertTrue(
            $this->detector->isBot('facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)')
        );
    }

    public function test_detects_twitterbot(): void
    {
        $this->assertTrue($this->detector->isBot('Twitterbot/1.0'));
    }

    public function test_detects_slackbot(): void
    {
        $this->assertTrue($this->detector->isBot('Slackbot-LinkExpanding 1.0'));
    }

    public function test_detects_curl(): void
    {
        $this->assertTrue($this->detector->isBot('curl/7.68.0'));
    }

    public function test_detects_empty_ua_as_bot(): void
    {
        $this->assertTrue($this->detector->isBot(''));
    }

    public function test_real_chrome_is_not_bot(): void
    {
        $this->assertFalse(
            $this->detector->isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
        );
    }

    public function test_real_safari_ios_is_not_bot(): void
    {
        $this->assertFalse(
            $this->detector->isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1')
        );
    }

    public function test_real_android_chrome_is_not_bot(): void
    {
        $this->assertFalse(
            $this->detector->isBot('Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36')
        );
    }
}
