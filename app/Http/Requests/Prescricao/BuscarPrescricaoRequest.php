<?php

namespace App\Http\Requests\Prescricao;

use Illuminate\Foundation\Http\FormRequest;

class BuscarPrescricaoRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {prescricao} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['prescricao' => $this->route('prescricao')]);
    }

    /**
     * Determina se o utilizador autenticado pode consultar uma prescrição.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do identificador da prescrição.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'prescricao' => ['required', 'uuid'],
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
            'prescricao.uuid' => 'A prescrição indicada não é válida.',
        ];
    }
}
