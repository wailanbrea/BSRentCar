<?php

namespace App\Http\Requests\Customer;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024), // 5 MB
            ],
        ];
    }
}
