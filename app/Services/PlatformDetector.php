<?php

namespace App\Services;

class PlatformDetector
{
    public function detect(string $userAgent): array
    {
        $isIOS = preg_match('/iPad|iPhone|iPod/', $userAgent) && ! str_contains($userAgent, 'Windows');
        $isAndroid = str_contains($userAgent, 'Android');
        $isTablet = preg_match('/iPad|tablet|Tablet/', $userAgent) && ! preg_match('/Mobile/', $userAgent);

        if ($isIOS) {
            preg_match('/OS ([\d_]+)/', $userAgent, $ver);
            return [
                'platform' => 'ios',
                'device_type' => $isTablet ? 'tablet' : 'mobile',
                'os_version' => isset($ver[1]) ? str_replace('_', '.', $ver[1]) : null,
                'browser' => $this->detectBrowser($userAgent),
            ];
        }

        if ($isAndroid) {
            preg_match('/Android ([\d.]+)/', $userAgent, $ver);
            return [
                'platform' => 'android',
                'device_type' => $isTablet ? 'tablet' : 'mobile',
                'os_version' => $ver[1] ?? null,
                'browser' => $this->detectBrowser($userAgent),
            ];
        }

        return [
            'platform' => 'desktop',
            'device_type' => 'desktop',
            'os_version' => null,
            'browser' => $this->detectBrowser($userAgent),
        ];
    }

    private function detectBrowser(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Edge') || str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Opera') || str_contains($ua, 'OPR') => 'Opera',
            str_contains($ua, 'Chrome') && ! str_contains($ua, 'Chromium') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome') => 'Safari',
            default => null,
        };
    }
}
