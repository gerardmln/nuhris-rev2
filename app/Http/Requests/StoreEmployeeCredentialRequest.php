<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeCredentialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $credentialType = (string) $this->input('credential_type', '');
        $requiresTitle = in_array($credentialType, ['seminars', 'degrees'], true);
        $requiresExpirationDate = $credentialType === 'prc';

        return [
            'credential_type' => ['required', 'in:resume,prc,seminars,degrees,ranking'],
            'title' => [$requiresTitle ? 'required' : 'nullable', 'string', 'max:255'],
            'expires_at' => [$requiresExpirationDate ? 'required' : 'nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'credential_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
