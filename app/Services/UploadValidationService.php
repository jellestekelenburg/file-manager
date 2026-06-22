<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;

final class UploadValidationService
{
    public function check(User $user, array $files, ?File $parent = null): array
    {
        $totalBytes = collect($files)->sum(fn ($file) => (int) $file['size']);
        $remainingBytes = max(0, $user->getMaxStorageSize() - $user->getStorageSize());

        if ($totalBytes > $remainingBytes) {
            return [
                'ok' => false,
                'code' => 'storage_limit_exceeded',
                'message' => 'Not enough storage available.',
                'total_upload_bytes' => $totalBytes,
                'remaining_bytes' => $remainingBytes,
                'errors' => [
                    [
                        'code' => 'storage_limit_exceeded',
                        'message' => 'You do not have enough storage available for this upload.',
                    ],
                ],
            ];
        }

        return [
            'ok' => true,
            'total_upload_bytes' => $totalBytes,
            'remaining_bytes' => $remainingBytes,
            'errors' => [],
        ];
    }
}
