<?php

namespace App\Http\Requests\Prescricao;

use App\Enums\Prescricao\EstadoPrescricao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarRevisaoPrescricoesRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode listar a fila de revisão.
     *
     * A autorização é reforçada no middleware `can:rever-prescricoes` da rota.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação dos parâmetros de consulta da fila de revisão.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', 'string', Rule::enum(EstadoPrescricao::class)],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'estado.in' => 'O estado indicado não é válido.',
            'por_pagina.integer' => 'O número de itens por página deve ser um número inteiro.',
            'por_pagina.min' => 'O número de itens por página deve ser no mínimo 1.',
            'por_pagina.max' => 'O número de itens por página não pode exceder 100.',
        ];
    }
}
