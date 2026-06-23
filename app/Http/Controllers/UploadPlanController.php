<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPlanRequest;
use App\Services\UploadPlanService;

class UploadPlanController extends Controller
{
    public function __invoke(UploadPlanRequest $request, UploadPlanService $service)
    {
        return response()->json(
            $service->makePlan(
                user: $request->user(),
                files: $request->validated('files'),
                parentId: $request->validated('parent_id') ?? null,
            )
        );
    }
}
