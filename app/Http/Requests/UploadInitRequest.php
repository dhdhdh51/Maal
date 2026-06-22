<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadInitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('videos.upload') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            'checksum' => ['nullable', 'string', 'max:128'],
            'content_type' => ['nullable', 'string', 'max:128'],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
