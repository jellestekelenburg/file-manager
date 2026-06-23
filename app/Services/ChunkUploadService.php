<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ChunkUploadService
{
    public function storeChunk(
        User $user,
        string $uploadId,
        string $uploadFileId,
        int $index,
        ?UploadedFile $chunk,
    ): array {
        $plan = $this->loadPlan($user, $uploadId);

        if (! $plan) {
            return $this->failed(
                uploadId: $uploadId,
                uploadFileId: $uploadFileId,
                code: 'upload_plan_not_found',
                message: 'Upload plan was not found.',
            );
        }

        $plannedFile = $this->findPlannedFile($plan, $uploadFileId);

        if (! $plannedFile) {
            return $this->failed(
                uploadId: $uploadId,
                uploadFileId: $uploadFileId,
                code: 'upload_file_not_found',
                message: 'Upload file was not found.',
            );
        }

        if (! $chunk) {
            return $this->failed(
                uploadId: $uploadId,
                uploadFileId: $uploadFileId,
                code: 'upload_chunk_not_found',
                message: 'Upload chunk was not found.',
            );
        }

        $totalChunks = (int) $plannedFile['total_chunks'];

        if ($index < 0 || $index >= $totalChunks) {
            return $this->failed(
                uploadId: $uploadId,
                uploadFileId: $uploadFileId,
                code: 'invalid_chunk_index',
                message: 'Upload chunk index was invalid.',
            );
        }

        Storage::putFileAs(
            $this->chunkDirectory($user, $uploadId, $uploadFileId),
            $chunk,
            $this->chunkFilename($index)
        );

        $receivedChunks = $this->countReceivedChunks(
            user: $user,
            uploadId: $uploadId,
            uploadFileId: $uploadFileId,
            totalChunks: $totalChunks,
        );

        return [
            'ok' => true,
            'upload_id' => $uploadId,
            'upload_file_id' => $uploadFileId,
            'chunk_index' => $index,
            'received_chunks' => $receivedChunks,
            'total_chunks' => $totalChunks,
            'progress' => round(($receivedChunks / $totalChunks) * 100, 2),
        ];
    }

    public function complete(
        User $user,
        string $uploadId,
        string $uploadFileId,
        ?int $parentId,
    ): array {
        // TODO:
        // - Load/validate the persisted upload plan.
        // - Confirm all expected chunks exist.
        // - Merge chunks into the final storage location.
        // - Verify final file size equals planned size.
        // - Create the File model in the selected parent folder.
        // - Add storage usage and clear caches.
        // - Delete temporary chunks.

        return [
            'ok' => false,
            'code' => 'not_implemented',
            'message' => 'Chunk completion endpoint is scaffolded but not implemented yet.',
            'upload_id' => $uploadId,
            'upload_file_id' => $uploadFileId,
        ];
    }

    private function loadPlan(User $user, string $uploadId): ?array
    {
        return Cache::get($this->cacheKey($user, $uploadId));
    }

    private function cacheKey(User $user, string $uploadId): string
    {
        return "upload-plan:{$user->id}:{$uploadId}";
    }

    private function findPlannedFile(array $plan, string $uploadFileId): ?array
    {
        return collect($plan['chunked_files'] ?? [])
            ->firstWhere('upload_file_id', $uploadFileId);
    }

    private function chunkDirectory(
        User $user,
        string $uploadId,
        string $uploadFileId,
    ): string {
        return "uploads/tmp/{$user->id}/{$uploadId}/{$uploadFileId}";
    }

    private function chunkPath(
        User $user,
        string $uploadId,
        string $uploadFileId,
        int $index,
    ): string {
        return $this->chunkDirectory($user, $uploadId, $uploadFileId)
            .'/'
            .$this->chunkFilename($index);
    }

    private function chunkFilename(int $index): string
    {
        return "{$index}.part";
    }

    private function countReceivedChunks(
        User $user,
        string $uploadId,
        string $uploadFileId,
        int $totalChunks
    ): int {
        $received = 0;

        for ($index = 0; $index < $totalChunks; $index++) {
            if (Storage::exists($this->chunkPath($user, $uploadId, $uploadFileId, $index))) {
                $received++;
            }
        }

        return $received;
    }

    private function failed(
        string $uploadId,
        string $uploadFileId,
        string $code,
        string $message,
    ): array {
        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'upload_id' => $uploadId,
            'upload_file_id' => $uploadFileId,
        ];
    }
}
