<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Support\SizeFormatter;
use Illuminate\Support\Facades\Cache;

class StorageUserService
{
    private function cacheKey(User $user): string
    {
        return "storage:stats:{$user->id}";
    }

    private function makeStats(int $used, int $max): array
    {
        return [
            'used_bytes' => $used,
            'max_bytes' => $max,
            'used_formatted' => SizeFormatter::formatBytes($used),
            'max_formatted' => SizeFormatter::formatBytes($max, 0),
            'is_full' => $max > 0 && $used >= $max,
            'percentage' => $max > 0 ? round(($used / $max) * 100, 2) : 0,
        ];
    }

    public function getCachedOrRecalculate(User $user): array
    {
        $key = $this->cacheKey($user);

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        return $this->recalculate($user);
    }

    public function addUsage(User $user, int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        $user->increment('used_storage', $bytes);
        $user->refresh();

        $stats = $this->makeStats((int) $user->used_storage, (int) $user->max_storage);

        Cache::forget($this->cacheKey($user));
        Cache::put($this->cacheKey($user), $stats, now()->addMinutes(10));
    }

    public function recalculate(User $user): array
    {
        $used = (int) File::query()
            ->where('created_by', $user->id)
            ->where('is_folder', false)
            ->sum('size');

        User::where('id', $user->id)->update(['used_storage' => $used]);

        $max = (int) $user->max_storage;
        $stats = $this->makeStats($used, $max);

        Cache::put($this->cacheKey($user), $stats, now()->addMinutes(10));

        return $stats;
    }
}
