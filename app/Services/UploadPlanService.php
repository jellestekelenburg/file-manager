<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class UploadPlanService
{
    private const int|float CHUNK_THRESHOLD = 100 * 1024 * 1024;
    private const int|float CHUNK_SIZE = 16 * 1024 * 1024;
    private const int MAX_BATCH_FILES = 10;
    private const int|float MAX_BATCH_BYTES = 100 * 1024 * 1024;

    public function makePlan(User $user, array $files, ?int $parentId): array
    {
        $totalBytes = collect($files)->sum(fn ($file) => (int) $file['size']);
        $remainingBytes = max(0, $user->getMaxStorageSize() - $user->getUsedStorageSize());

        if ($totalBytes > $remainingBytes) {
            return [
                'ok' => false,
                'code' => 'storage_limit_exceeded',
                'message' => 'Not enough storage available.',
                'errors' => [[
                    'code' => 'storage_limit_exceeded',
                    'message' => 'You do not have enough storage for this upload.',
                ]],
            ];
        }

        $smallFiles = collect($files)
            ->filter(fn ($file) => (int) $file['size'] < self::CHUNK_THRESHOLD)
            ->values();

        $largeFiles = collect($files)
            ->filter(fn ($file) => (int) $file['size'] >= self::CHUNK_THRESHOLD)
            ->values();

        $plan = [
            'ok' => true,
            'upload_id' => (string) str()->uuid(),
            'threshold_bytes' => self::CHUNK_THRESHOLD,
            'chunk_size' => self::CHUNK_SIZE,
            'max_concurrency' => 3,
            'small_file_batches' => $this->makeSmallFileBatches($smallFiles),
            'chunked_files' => $largeFiles->map(fn ($file) => [
                'client_id' => $file['client_id'],
                'upload_file_id' => (string) str()->uuid(),
                'name' => $file['name'],
                'size' => (int) $file['size'],
                'total_chunks' => (int) ceil($file['size'] / self::CHUNK_SIZE),
                'relative_path' => $file['relative_path'] ?? null,
            ])->values(),
            'errors' => [],
        ];

        Cache::put("upload-plan:{$user->id}:{$plan['upload_id']}", $plan, now()->addHours(2));

        return $plan;
    }

    private function makeSmallFileBatches($files): array
    {
        $batches = [];
        $current = [];
        $currentBytes = 0;

        foreach ($files as $file) {
            $size = (int) $file['size'];

            if (
                count($current) >= self::MAX_BATCH_FILES ||
                $currentBytes + $size > self::MAX_BATCH_BYTES
            ) {
                $batches[] = [
                    'batch_id' => 'batch_'.(count($batches) + 1),
                    'files' => $current,
                ];

                $current = [];
                $currentBytes = 0;
            }

            $current[] = $file['client_id'];
            $currentBytes += $size;
        }

        if ($current) {
            $batches[] = [
                'batch_id' => 'batch_'.(count($batches) + 1),
                'files' => $current,
            ];
        }

        return $batches;
    }
}
