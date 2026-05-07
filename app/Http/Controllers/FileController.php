<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilesActionsRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\StorageUserService;
use App\Services\ZipCreatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FileController extends Controller
{
    protected StorageUserService $storageUserService;

    public function __construct(StorageUserService $storageUserService)
    {
        $this->storageUserService = $storageUserService;
    }

    public function myFiles(Request $request, ?string $folder = null)
    {
        $sortableColumns = [
            'name' => 'files.name',
            'updated_at' => 'files.updated_at',
            'size' => 'files.size',
        ];

        $sortBy = $request->query('sortBy', 'size');
        $sortDirection = $request->query('sortDirection', 'desc');

        $sortColumn = $sortableColumns[$sortBy] ?? 'files.size';

        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        if ($folder) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder)
                ->firstOrFail();
        } else {
            $folder = $this->getRoot();
        }

        // $cache_key = 'Files_'.Auth::id().$folder->name.'page_'.$request->page;
        // todo: cache files and clear cache when change in folder, maybe save on folder so we can
        // clear folder and touch (extend) cache when revisit before cache expire

        $files = File::query()
            ->where('parent_id', $folder->id)
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        $sort = [
            'by' => $sortBy,
            'direction' => $sortDirection,
        ];

        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);
        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors', 'sort'));
    }

    public function trash(Request $request)
    {
        $files = File::onlyTrashed()
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('files.deleted_at', 'desc')
            ->paginate(10);

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('Trash', compact('files'));
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

    public function destroy(FilesActionsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;

            foreach ($children as $child) {
                $child->moveToTrash();
            }
        }

        foreach ($data['ids'] ?? [] as $id) {
            $file = File::find($id);
            if ($file) {
                $file->moveToTrash();
            }
        }

        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function download(FilesActionsRequest $request, ZipCreatorService $zipCreator)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (! $all && empty($ids)) {
            return [
                'message' => 'Please select at least one file to download.',
            ];
        }

        if ($all) {
            $url = $zipCreator->createZip($parent->children);
            $filename = $parent->name.'.zip';
        } else {
            if (count($ids) == 1) {
                $file = File::find($ids[0]);
                if ($file->is_folder) {
                    if ($file->children->count() == 0) {
                        return [
                            'message' => 'This folder is empty.',
                        ];
                    }
                    $url = $zipCreator->createZip($file->children);
                    $filename = $file->name.'.zip';
                } else {
                    $basename = pathinfo($file->storage_path, PATHINFO_BASENAME);
                    Storage::disk('public')->put($basename, Storage::get($file->storage_path));
                    $url = Storage::disk('public')->url($basename);
                    $filename = $file->name;
                }
            } else {
                $file = File::query()->whereIn('id', $ids)->get();
                $url = $zipCreator->createZip($file);
                $filename = $parent->name.'.zip';
            }
        }

        return [
            'url' => $url,
            'filename' => $filename,
        ];
    }

    public function restore(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
        }

        foreach ($children as $child) {
            $child->restore();
        }

        return to_route('trash');
    }

    public function deleteForever(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
        }

        foreach ($children as $child) {
            $child->deleteForever();
        }

        $this->storageUserService->clearCache(Auth::user());

        return to_route('trash');
    }
}
