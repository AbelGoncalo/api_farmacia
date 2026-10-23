<?php

namespace App\Http\Requests\Encomenda;

use Illuminate\Foundation\Http\FormRequest;

class CancelarEncomendaRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {encomenda} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['encomenda' => $this->route('encomenda')]);
    }

    /**
     * Determina se o utilizador autenticado pode cancelar encomendas.
     *
     * O cliente só pode cancelar as próprias encomendas ainda não pagas;
     * essa regra de negócio é aplicada no serviço (transição de estado).
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
