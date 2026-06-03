<?php

namespace App\Services;

use App\Models\Link;

class ShortCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

    private const RESERVED = ['l', 'go', 'r', 'api', 'app', 'admin', 'status', 'health', 'ping'];

    public function generate(int $domainId, int $length = 6): string
    {
        $attempts = 0;
        do {
            if ($attempts >= 5) {
                $length++;
                $attempts = 0;
            }
            $code = $this->random($length);
            $attempts++;
        } while (
            in_array($code, self::RESERVED, true) ||
            Link::where('short_code', $code)->where('domain_id', $domainId)->exists()
        );

        return $code;
    }

    public function isCustomCodeAvailable(string $code, int $domainId, ?int $excludeLinkId = null): bool
    {
        if (in_array($code, self::RESERVED, true)) {
            return false;
        }

        return ! Link::where('short_code', $code)
            ->where('domain_id', $domainId)
            ->when($excludeLinkId, fn ($q) => $q->where('id', '!=', $excludeLinkId))
            ->exists();
    }

    private function random(int $length): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }
}
