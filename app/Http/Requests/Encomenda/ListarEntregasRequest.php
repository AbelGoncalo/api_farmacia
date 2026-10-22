<?php

namespace App\Http\Requests\Encomenda;

use App\Enums\Encomenda\EstadoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListarEntregasRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode listar entregas.
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
            'estado' => ['nullable', 'string', new Enum(EstadoEntrega::class)],
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
            'estado.enum' => 'O estado deve ser PENDENTE, EM_TRANSPORTE ou ENTREGUE.',
            'por_pagina.integer' => 'O número de itens por página deve ser um número inteiro.',
            'por_pagina.min' => 'O número de itens por página deve ser no mínimo 1.',
            'por_pagina.max' => 'O número de itens por página não pode exceder 100.',
        ];
    }
}
