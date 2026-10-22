<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizaCampos;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use NormalizaCampos;

    protected function prepareForValidation(): void
    {
        $this->normalizarCampos(['email']);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:180'],
            'palavra_passe' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email indicado não é válido.',
            'palavra_passe.required' => 'A palavra-passe é obrigatória.',
        ];
    }
}
