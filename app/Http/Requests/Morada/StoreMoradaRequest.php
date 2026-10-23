<?php

namespace App\Http\Requests\Morada;

use App\Http\Requests\Concerns\NormalizaCampos;
use Illuminate\Foundation\Http\FormRequest;

class StoreMoradaRequest extends FormRequest
{
    use NormalizaCampos;

    protected function prepareForValidation(): void
    {
        $this->normalizarCampos([
            'etiqueta',
            'nome_destinatario',
            'telefone',
            'provincia',
            'municipio',
            'bairro',
            'rua',
            'numero_porta',
            'referencia',
        ]);
    }

    /**
     * Determina se o utilizador autenticado pode criar moradas.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do corpo do pedido de criação de morada.
     *
     * `ativa` não é aceite no momento da criação: a morada nasce ativa.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'etiqueta' => ['nullable', 'string', 'max:80'],
            'nome_destinatario' => ['required', 'string', 'max:150'],
            'telefone' => ['required', 'string', 'max:30'],
            'provincia' => ['required', 'string', 'max:100'],
            'municipio' => ['required', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:150'],
            'rua' => ['nullable', 'string', 'max:150'],
            'numero_porta' => ['nullable', 'string', 'max:30'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'predefinida' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensagens de validação personalizadas, em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'etiqueta.max' => 'A etiqueta não pode exceder 80 caracteres.',
            'nome_destinatario.required' => 'O nome do destinatário é obrigatório.',
            'nome_destinatario.max' => 'O nome do destinatário não pode exceder 150 caracteres.',
            'telefone.required' => 'O telefone é obrigatório.',
            'telefone.max' => 'O telefone não pode exceder 30 caracteres.',
            'provincia.required' => 'A província é obrigatória.',
            'provincia.max' => 'A província não pode exceder 100 caracteres.',
            'municipio.required' => 'O município é obrigatório.',
            'municipio.max' => 'O município não pode exceder 100 caracteres.',
            'bairro.required' => 'O bairro é obrigatório.',
            'bairro.max' => 'O bairro não pode exceder 150 caracteres.',
            'rua.max' => 'A rua não pode exceder 150 caracteres.',
            'numero_porta.max' => 'O número porta não pode exceder 30 caracteres.',
            'referencia.max' => 'A referência não pode exceder 255 caracteres.',
            'predefinida.boolean' => 'O campo predefinida deve ser verdadeiro ou falso.',
        ];
    }
}
