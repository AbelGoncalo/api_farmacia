<?php

namespace App\Http\Requests\Prescricao;

use Illuminate\Foundation\Http\FormRequest;

class ListarPrescricoesRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode listar prescrições.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação dos parâmetros de consulta da listagem.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
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
            'por_pagina.integer' => 'O número de itens por página deve ser um número inteiro.',
            'por_pagina.min' => 'O número de itens por página deve ser no mínimo 1.',
            'por_pagina.max' => 'O número de itens por página não pode exceder 100.',
        ];
    }
}
