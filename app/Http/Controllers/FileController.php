<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\StorageUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FileController extends Controller
{
    public function myFiles(StorageUserService $storageService, ?string $folder = null)
    {
        $storage = $storageService->getCachedOrRecalculate(Auth::user());

        if ($folder) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder)
                ->firstOrFail();
        } else {
            $folder = $this->getRoot();
        }
        $files = File::query()
            ->where('parent_id', $folder->id)
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('files.created_at', 'desc')
            ->paginate(10);

        $files = FileResource::collection($files);
        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);
        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors', 'storage'));
    }

    public function createFolder(StoreFolderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $parent = $request->parent;

        if (! $parent) {
            $parent = $this->getRoot();
        }

        $file = new File;
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);

        return redirect()->back();
    }

    public function store(StoreFileRequest $request, StorageUserService $storageService)
    {
        $totalUploadedBytes = 0;

        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (! $parent) {
            $parent = $this->getRoot();
        }

        if (! empty($fileTree)) {
            $totalUploadedBytes = $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                $totalUploadedBytes = $this->saveFile($file, $user, $parent);
            }
        }

        $storageService->addUsage($user, $totalUploadedBytes);
    }

    private function getRoot()
    {
        return File::query()->where('created_by', Auth::id())->whereIsRoot()->firstOrFail();
    }

    public function saveFileTree($fileTree, $parent, $user): int
    {
        $total = 0;
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {

                $folder = new File;
                $folder->is_folder = true;
                $folder->name = $name;

                $parent->appendNode($folder);
                $total += $this->saveFileTree($file, $folder, $user);
            } else {
                $total += $this->saveFile($file, $user, $parent);
            }
        }

        return $total;
    }

    private function saveFile($file, $user, $parent): int
    {

        $size = (int) $file->getSize();

        /* @var UploadedFile $file */
        $path = $file->store('/files'.$user->id);

        $model = new File;
        $model->is_folder = false;
        $model->storage_path = $path;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getClientMimeType();
        $model->size = $file->getSize();

        $parent->appendNode($model);

        return $size;
    }
}
