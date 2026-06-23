<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class ChunkUploadService
{
    public function storeChunk(
        User $user,
        string $uploadId,
        string $uploadFileId,
        int $index,
        ?UploadedFile $chunk,
    ): array {
        // TODO:
        // - Load/validate the persisted upload plan by $uploadId.
        // - Check that $uploadFileId belongs to that plan and user.
        // - Check that $index is between 0 and total_chunks - 1.
        // - Store chunk temporarily, for example:
        //   storage/app/uploads/tmp/{user_id}/{upload_id}/{upload_file_id}/{index}.part
        // - Mark the chunk as received.
        // - Return current chunk progress.

        return [
            'ok' => false,
            'code' => 'not_implemented',
            'message' => 'Chunk upload endpoint is scaffolded but not implemented yet.',
            'upload_id' => $uploadId,
            'upload_file_id' => $uploadFileId,
            'chunk_index' => $index,
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
}
