<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ChunkUploadService
{
    public function __construct(
        private readonly StoreUploadedFile $storeUploadedFile,
        private readonly StorageUserService $storageUserService,
        private readonly UploadBatchService $UploadBatchService,
    ) {}

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
        @set_time_limit(300);

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

        $totalChunks = (int) $plannedFile['total_chunks'];

        for ($index = 0; $index < $totalChunks; $index++) {
            if (! Storage::exists($this->chunkPath($user, $uploadId, $uploadFileId, $index))) {
                return $this->failed(
                    uploadId: $uploadId,
                    uploadFileId: $uploadFileId,
                    code: 'missing_chunks',
                    message: 'Not all chunks have been uploaded yet or are not found',
                );
            }
        }

        $localMergedPath = storage_path("app/uploads/tmp/{$user->id}/{$uploadId}/{$uploadFileId}/merged");

        if (! is_dir(dirname($localMergedPath))) {
            mkdir(dirname($localMergedPath), 0775, true);
        }

        $target = fopen($localMergedPath, 'wb');

        for ($index = 0; $index < $totalChunks; $index++) {
            $chunkPath = $this->chunkPath($user, $uploadId, $uploadFileId, $index);

            $source = Storage::readStream($chunkPath);

            if ($source === false) {
                fclose($target);

                return $this->failed(
                    uploadId: $uploadId,
                    uploadFileId: $uploadFileId,
                    code: 'chunk_stream_failed',
                    message: 'Could not read uploaded chunk.',
                );
            }

            stream_copy_to_stream($source, $target);
            fclose($source);
        }

        fclose($target);

        if (filesize($localMergedPath) !== (int) $plannedFile['size']) {
            return $this->failed(
                uploadId: $uploadId,
                uploadFileId: $uploadFileId,
                code: 'invalid_file_size',
                message: 'Merged file size does not match the upload plan.'
            );
        }

        $parent = $this->resolveParent($user, $parentId);

        $targetParent = $this->UploadBatchService->resolveTargetParentFromRelativePath(
            user: $user,
            rootParent: $parent,
            relativePath: $plannedFile['relative_path'] ?? null,
        );

        $uploadedFile = new UploadedFile(
            $localMergedPath,
            $plannedFile['name'],
            null,
            null,
            true,
        );

        $model = $this->storeUploadedFile->handle(
            file: $uploadedFile,
            user: $user,
            parent: $targetParent,
        );

        $this->storageUserService->addUsage($user, (int) $model->size);

        Storage::deleteDirectory(
            $this->chunkDirectory($user, $uploadId, $uploadFileId),
        );

        @unlink($localMergedPath);

        return [
            'ok' => true,
            'upload_id' => $uploadId,
            'upload_file_id' => $uploadFileId,
            'file' => [
                'id' => $model->id,
                'name' => $model->name,
                'size' => $model->size,
                'status' => 'done',
            ],
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

    private function resolveParent(User $user, ?int $parentId): File
    {
        if ($parentId) {
            return File::query()
                ->where('id', $parentId)
                ->where('created_by', $user->id)
                ->where('is_folder', true)
                ->firstOrFail();
        }

        return File::query()
            ->where('created_by', $user->id)
            ->whereIsRoot()
            ->firstOrFail();
    }
}
