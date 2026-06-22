<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCheckRequest;
use Illuminate\Http\Request;
use App\Services\UploadValidationService;

class UploadCheckController extends Controller
{
    public function __invoke(UploadCheckRequest $request, UploadValidationService $service)
    {
        $result = $service->check(
            user: $request->user(),
            files: $request->files(),
            parent: $request->parent,
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
