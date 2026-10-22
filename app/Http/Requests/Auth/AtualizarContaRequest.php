<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizaCampos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AtualizarContaRequest extends FormRequest
{
    use NormalizaCampos;

    private const CAMPOS_ATUALIZAVEIS = ['nome', 'email', 'telefone', 'nif'];

    protected function prepareForValidation(): void
    {
        $this->normalizarCampos(self::CAMPOS_ATUALIZAVEIS);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($this->user()?->getAuthIdentifier()),
            ],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nif' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.max' => 'O nome não pode exceder 150 caracteres.',
            'nome.required' => 'O nome não pode ficar vazio.',
            'email.required' => 'O email não pode ficar vazio.',
            'email.email' => 'O email indicado não é válido.',
            'email.unique' => 'Já existe uma conta com este email.',
            'telefone.max' => 'O telefone não pode exceder 30 caracteres.',
            'nif.max' => 'O NIF não pode exceder 30 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $campos = array_intersect_key($this->all(), array_flip(self::CAMPOS_ATUALIZAVEIS));

            if ($campos === []) {
                $validator->errors()->add('dados', 'Indica pelo menos um campo para atualizar.');
            }
        });
    }
}
