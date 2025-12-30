<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Platform Status Model
 *
 * Represents the online/offline status of a store on delivery platforms
 */
class PlatformStatus extends Model
{
    protected $table = 'platform_status';

    protected $fillable = [
        'shop_id',
        'platform',
        'is_online',
        'items_synced',
        'items_total',
        'store_name',
        'store_url',
        'last_checked_at',
        'last_check_status',
        'last_error',
        'raw_html',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'items_synced' => 'integer',
        'items_total' => 'integer',
        'last_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scopes
     */

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    public function scopeForShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeRecentlyChecked($query, int $minutes = 30)
    {
        return $query->where('last_checked_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeStale($query, int $minutes = 30)
    {
        return $query->where(function ($q) use ($minutes) {
            $q->where('last_checked_at', '<', now()->subMinutes($minutes))
              ->orWhereNull('last_checked_at');
        });
    }

    /**
     * Accessors & Mutators
     */

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_online ? 'online' : 'offline';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_online ? 'green' : 'red';
    }

    public function getLastCheckedHumanAttribute(): ?string
    {
        return $this->last_checked_at?->diffForHumans();
    }

    public function getIsFreshAttribute(): bool
    {
        if (!$this->last_checked_at) {
            return false;
        }
        return $this->last_checked_at->greaterThan(now()->subMinutes(30));
    }

    /**
     * Helper Methods
     */

    public static function getStatusForShop(string $shopId): array
    {
        $statuses = self::where('shop_id', $shopId)->get();

        return [
            'grab' => $statuses->firstWhere('platform', 'grab'),
            'foodpanda' => $statuses->firstWhere('platform', 'foodpanda'),
            'deliveroo' => $statuses->firstWhere('platform', 'deliveroo'),
        ];
    }

    public static function getOnlinePercentage(): float
    {
        $total = self::count();
        if ($total === 0) {
            return 0;
        }

        $online = self::where('is_online', true)->count();
        return round(($online / $total) * 100, 2);
    }

    public static function getStatsByPlatform(): array
    {
        $platforms = ['grab', 'foodpanda', 'deliveroo'];
        $stats = [];

        foreach ($platforms as $platform) {
            $total = self::where('platform', $platform)->count();
            $online = self::where('platform', $platform)->where('is_online', true)->count();

            $stats[$platform] = [
                'total' => $total,
                'online' => $online,
                'offline' => $total - $online,
                'percentage' => $total > 0 ? round(($online / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Update or create status
     */
    public static function updateStatus(array $data): self
    {
        return self::updateOrCreate(
            [
                'shop_id' => $data['shop_id'],
                'platform' => $data['platform'],
            ],
            array_merge($data, [
                'last_checked_at' => now(),
            ])
        );
    }
}
