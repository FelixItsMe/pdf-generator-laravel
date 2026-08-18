<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id'  => ['nullable', 'exists:pdf_templates,id'],
            'image_ids'    => ['nullable', 'array'],
            'image_ids.*'  => ['integer', 'exists:uploaded_images,id'],
            'variables'    => ['nullable', 'array'],
            'variables.*'  => ['string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.exists'   => 'The selected template does not exist.',
            'image_ids.*.exists'   => 'One or more image IDs are invalid.',
        ];
    }
}
