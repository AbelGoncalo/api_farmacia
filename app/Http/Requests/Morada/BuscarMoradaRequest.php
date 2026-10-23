<?php

namespace App\Http\Requests\Morada;

use Illuminate\Foundation\Http\FormRequest;

class BuscarMoradaRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {morada} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['morada' => $this->route('morada')]);
    }

    /**
     * Determina se o utilizador autenticado pode consultar uma morada.
     *
     * A verificação de posse (morada ser do próprio) é feita no serviço.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do identificador da morada.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'morada' => ['required', 'uuid'],
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
        ];
    }
}
