<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends ParentIdBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(),
            [
                'name' => [
                    'required',
                    'string',
                    Rule::unique(File::class, 'name')
                        ->where('created_by', auth()->id())
                        ->where('parent_id', $this->parent_id)
                        ->whereNull('deleted_at'),
                ],
            ]
        );
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Folder name already exists',
        ];
    }
}
