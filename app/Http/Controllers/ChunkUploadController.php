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
