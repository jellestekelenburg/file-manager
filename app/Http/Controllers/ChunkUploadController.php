<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteChunkUploadRequest;
use App\Http\Requests\StoreUploadChunkRequest;
use App\Services\ChunkUploadService;
use Illuminate\Http\JsonResponse;

class ChunkUploadController extends Controller
{
    public function store(
        StoreUploadChunkRequest $request,
        ChunkUploadService $service,
        string $uploadId,
        string $uploadFileId,
        int $index,
    ): JsonResponse {
        // Purpose:
        // Store one chunk for one large file from the upload plan.
        //
        // This endpoint should eventually:
        // 1. Confirm upload_id/upload_file_id/index are expected for this user.
        // 2. Store the chunk in a temporary location.
        // 3. Mark this chunk as received.
        // 4. Return progress data for the frontend queue.
        //
        // Do not create the final File model here. That should happen in complete().
        $result = $service->storeChunk(
            user: $request->user(),
            uploadId: $uploadId,
            uploadFileId: $uploadFileId,
            index: $index,
            chunk: $request->file('chunk'),
        );

        return response()->json($result);
    }

    public function complete(
        CompleteChunkUploadRequest $request,
        ChunkUploadService $service,
        string $uploadId,
        string $uploadFileId,
    ): JsonResponse {
        // Purpose:
        // Merge all received chunks into one final file.
        //
        // This endpoint should eventually:
        // 1. Confirm all chunks for this file are present.
        // 2. Merge chunks in index order.
        // 3. Verify final byte size against the planned file size.
        // 4. Store final file and create the File model.
        // 5. Clean temporary chunk files.
        $result = $service->complete(
            user: $request->user(),
            uploadId: $uploadId,
            uploadFileId: $uploadFileId,
            parentId: $request->integer('parent_id') ?: null,
        );

        return response()->json($result);
    }
}
