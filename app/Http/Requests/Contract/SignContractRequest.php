<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class SignContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controlado por el controlador/policy
    }

    public function rules(): array
    {
        return [
            'printed_name' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
