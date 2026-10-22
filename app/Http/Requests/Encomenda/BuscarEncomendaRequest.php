<?php

namespace App\Http\Requests\Encomenda;

use Illuminate\Foundation\Http\FormRequest;

class BuscarEncomendaRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {encomenda} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['encomenda' => $this->route('encomenda')]);
    }

    /**
     * Determina se o utilizador autenticado pode consultar uma encomenda.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do identificador da encomenda.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'encomenda' => ['required', 'uuid'],
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
            'encomenda.uuid' => 'A encomenda indicada não é válida.',
        ];
    }
}
