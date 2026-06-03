<?php

namespace App\Services;

class BotDetector
{
    private const PATTERNS = [
        'bot', 'crawler', 'spider', 'scraper',
        'facebookexternalhit', 'Twitterbot', 'LinkedInBot',
        'Slackbot', 'WhatsApp', 'TelegramBot',
        'Googlebot', 'bingbot', 'DuckDuckBot', 'Baiduspider', 'YandexBot',
        'curl/', 'wget/', 'python-requests', 'axios/', 'java/',
        'Go-http-client', 'libwww-perl', 'HTTPie',
    ];

    public function isBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }
        foreach (self::PATTERNS as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
