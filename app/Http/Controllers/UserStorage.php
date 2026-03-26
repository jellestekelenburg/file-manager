<?php

namespace App\Http\Controllers;

use AllowDynamicProperties;
use App\Services\StorageUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[AllowDynamicProperties]
class UserStorage extends Controller
{
    public function __construct(StorageUserService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function __invoke(): JsonResponse
    {
        $storage = $this->storageService->getCachedOrRecalculate(Auth::user());

        return response()->json($storage);
    }
}
