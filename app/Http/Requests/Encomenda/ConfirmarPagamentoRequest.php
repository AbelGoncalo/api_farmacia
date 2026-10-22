<?php

namespace App\Http\Requests\Encomenda;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarPagamentoRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {pagamento} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['pagamento' => $this->route('pagamento')]);
    }

    /**
     * Determina se o utilizador autenticado pode confirmar um pagamento.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação da confirmação de um pagamento.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'pagamento' => ['required', 'uuid'],
            'id_transacao' => ['nullable', 'string', 'max:150'],
            'dados_resposta_operador' => ['nullable', 'array'],
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
            'pagamento.uuid' => 'O pagamento indicado não é válido.',
            'id_transacao.max' => 'O identificador de transação não pode exceder 150 caracteres.',
            'dados_resposta_operador.array' => 'Os dados da resposta do operador devem ser um objeto.',
        ];
    }
}
