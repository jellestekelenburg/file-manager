<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZipCreatorService
{
    public function createZip($files): string
    {
        $publicDisk = Storage::disk('public');
        $zipPath = 'zip/'.Str::random().'.zip';
        $publicDisk->makeDirectory(dirname($zipPath));
        $zipFile = $publicDisk->path($zipPath);

        $zip = new \ZipArchive;

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }

        $zip->close();

        return $publicDisk->url($zipPath);
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors.$file->name.'/');
            } else {
                if (! $file->storage_path || ! Storage::exists($file->storage_path)) {
                    continue;
                }

                $zip->addFile(
                    Storage::path($file->storage_path),
                    $ancestors.$file->name,
                );
            }
        }
    }
}
