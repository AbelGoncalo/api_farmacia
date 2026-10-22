<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizaCampos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistarRequest extends FormRequest
{
    use NormalizaCampos;

    protected function prepareForValidation(): void
    {
        $this->normalizarCampos(['nome', 'email', 'telefone', 'nif']);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')],
            'telefone' => ['nullable', 'string', 'max:30'],
            'nif' => ['nullable', 'string', 'max:30'],
            'palavra_passe' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode exceder 150 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email indicado não é válido.',
            'email.unique' => 'Já existe uma conta com este email.',
            'telefone.max' => 'O telefone não pode exceder 30 caracteres.',
            'nif.max' => 'O NIF não pode exceder 30 caracteres.',
            'palavra_passe.required' => 'A palavra-passe é obrigatória.',
            'palavra_passe.confirmed' => 'A confirmação da palavra-passe não coincide.',
        ];
    }
}
