<?php

namespace App\Services;

class SsrfValidator
{
    private const BLOCKED_CIDRS = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        '100.64.0.0/10',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    public function isSafe(string $url): bool
    {
        $parsed = parse_url($url);

        if (($parsed['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if (! $host) {
            return false;
        }

        $ips = @gethostbynamel($host);
        if ($ips === false) {
            return false;
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private function isPrivateIp(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return true; // IPv6 or invalid — block
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (str_contains($cidr, ':')) {
                continue; // skip IPv6 for ip2long
            }
            [$network, $bits] = explode('/', $cidr);
            $mask = ~((1 << (32 - (int) $bits)) - 1);
            if ((ip2long($network) & $mask) === ($long & $mask)) {
                return true;
            }
        }

        return false;
    }
}
