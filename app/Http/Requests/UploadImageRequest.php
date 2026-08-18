<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'             => ['required', 'array', 'min:1', 'max:20'],
            'images.*'           => [
                'required',
                'image',
                'mimes:jpeg,png,gif,webp',
                'max:10240', // 10 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required'     => 'Please upload at least one image.',
            'images.max'          => 'You may upload up to 20 images at once.',
            'images.*.image'      => 'Each file must be a valid image.',
            'images.*.mimes'      => 'Supported formats: JPEG, PNG, GIF, WebP.',
            'images.*.max'        => 'Each image must not exceed 10 MB.',
        ];
    }
}
