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
        $result = $service->complete(
            user: $request->user(),
            uploadId: $uploadId,
            uploadFileId: $uploadFileId,
            parentId: $request->integer('parent_id') ?: null,
        );

        return response()->json($result);
    }
}
