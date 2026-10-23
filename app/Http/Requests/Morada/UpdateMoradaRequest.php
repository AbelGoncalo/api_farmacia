<?php

namespace App\Http\Requests\Morada;

use App\Http\Requests\Concerns\NormalizaCampos;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMoradaRequest extends FormRequest
{
    use NormalizaCampos;

    /**
     * Injeta o parâmetro de rota {morada} nos dados de validação
     * e normaliza os campos de texto do corpo.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['morada' => $this->route('morada')]);

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
     * Determina se o utilizador autenticado pode alterar moradas.
     *
     * A verificação de posse (morada ser do próprio) é feita no serviço.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação para atualização parcial (PATCH).
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'morada' => ['required', 'uuid'],
            'etiqueta' => ['sometimes', 'nullable', 'string', 'max:80'],
            'nome_destinatario' => ['sometimes', 'string', 'max:150'],
            'telefone' => ['sometimes', 'string', 'max:30'],
            'provincia' => ['sometimes', 'string', 'max:100'],
            'municipio' => ['sometimes', 'string', 'max:100'],
            'bairro' => ['sometimes', 'string', 'max:150'],
            'rua' => ['sometimes', 'nullable', 'string', 'max:150'],
            'numero_porta' => ['sometimes', 'nullable', 'string', 'max:30'],
            'referencia' => ['sometimes', 'nullable', 'string', 'max:255'],
            'predefinida' => ['sometimes', 'boolean'],
            'ativa' => ['sometimes', 'boolean'],
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
            'morada.uuid' => 'A morada indicada não é válida.',
            'etiqueta.max' => 'A etiqueta não pode exceder 80 caracteres.',
            'nome_destinatario.max' => 'O nome do destinatário não pode exceder 150 caracteres.',
            'telefone.max' => 'O telefone não pode exceder 30 caracteres.',
            'provincia.max' => 'A província não pode exceder 100 caracteres.',
            'municipio.max' => 'O município não pode exceder 100 caracteres.',
            'bairro.max' => 'O bairro não pode exceder 150 caracteres.',
            'rua.max' => 'A rua não pode exceder 150 caracteres.',
            'numero_porta.max' => 'O número porta não pode exceder 30 caracteres.',
            'referencia.max' => 'A referência não pode exceder 255 caracteres.',
            'predefinida.boolean' => 'O campo predefinida deve ser verdadeiro ou falso.',
            'ativa.boolean' => 'O campo ativa deve ser verdadeiro ou falso.',
        ];
    }
}
