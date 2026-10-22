<?php

namespace App\Http\Requests\Encomenda;

use Illuminate\Foundation\Http\FormRequest;

class IniciarTransporteRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {entrega} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['entrega' => $this->route('entrega')]);
    }

    /**
     * Determina se o utilizador autenticado pode iniciar o transporte de uma entrega.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do identificador da entrega.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'entrega' => ['required', 'uuid'],
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
            'entrega.uuid' => 'A entrega indicada não é válida.',
        ];
    }
}
