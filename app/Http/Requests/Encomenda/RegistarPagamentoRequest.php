<?php

namespace App\Http\Requests\Encomenda;

use App\Enums\Encomenda\MetodoPagamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistarPagamentoRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {encomenda} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['encomenda' => $this->route('encomenda')]);
    }

    /**
     * Determina se o utilizador autenticado pode registar um pagamento.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do registo de um pagamento.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'encomenda' => ['required', 'uuid'],
            'metodo' => ['required', 'string', new Enum(MetodoPagamento::class)],
            'referencia' => ['nullable', 'string', 'max:150'],
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
            'metodo.required' => 'O método de pagamento é obrigatório.',
            'metodo.enum' => 'O método de pagamento indicado não é válido.',
            'referencia.max' => 'A referência não pode exceder 150 caracteres.',
        ];
    }
}
