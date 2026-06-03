<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\LinkClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LogLinkClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $linkId,
        private readonly string $ip,
        private readonly string $userAgent,
        private readonly string $outcome,
        private readonly string $platform,
        private readonly string $deviceType,
        private readonly ?string $osVersion,
        private readonly ?string $browser,
        private readonly ?string $referrer,
        private readonly ?string $utmSource,
        private readonly ?string $utmMedium,
        private readonly ?string $utmCampaign,
    ) {}

    public function handle(): void
    {
        $ipHash = hash('sha256', $this->ip . $this->linkId . date('Y-m-d'));
        $cacheKey = "click_unique:{$ipHash}";

        $isUnique = ! Cache::has($cacheKey);
        if ($isUnique) {
            Cache::put($cacheKey, 1, now()->addDay());
        }

        LinkClick::create([
            'link_id' => $this->linkId,
            'clicked_at' => now(),
            'platform' => $this->platform,
            'device_type' => $this->deviceType,
            'os_version' => $this->osVersion,
            'browser' => $this->browser,
            'outcome' => $this->outcome,
            'ip_hash' => $ipHash,
            'referrer_domain' => $this->referrer ? parse_url($this->referrer, PHP_URL_HOST) : null,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'is_unique' => $isUnique,
        ]);

        // Only count non-bot clicks
        if ($this->outcome !== 'bot_filtered') {
            DB::table('links')->where('id', $this->linkId)->increment('click_count');
        }
    }
}
