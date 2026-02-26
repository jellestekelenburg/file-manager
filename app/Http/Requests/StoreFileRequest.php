<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;

class StoreFileRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parentId = $this->parent?->id
            ?? File::query()
                ->where('created_by', Auth::id())
                ->whereIsRoot()
                ->value('id');

        return array_merge(parent::rules(), [
            'files' => [
                'required',
                'array',
                'min:1',
            ],
            'files.*' => [
                'required',
                'file',
                function ($attribute, $value, $fail) use ($parentId) {
                    /* @var $value UploadedFile */
                    $file = File::query()
                        ->where('name', $value->getClientOriginalName())
                        ->where('files.created_by', auth()->id())
                        ->where('parent_id', $parentId)
                        ->whereNull('deleted_at')->exists();

                    if ($file) {
                        $fail('File "'.$value->getClientOriginalName().'" already exists.');
                    }
                },
            ],
        ]);
    }
}
