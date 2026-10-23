<?php

namespace App\Http\Requests\Encomenda;

use App\Enums\Encomenda\TipoEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CriarEncomendaRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode criar uma encomenda.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação aplicadas ao corpo do pedido de criação de encomenda.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tipo_entrega' => ['required', 'string', new Enum(TipoEntrega::class)],
            'morada_id' => [
                'nullable',
                'uuid',
                Rule::requiredIf($this->input('tipo_entrega') === TipoEntrega::ENTREGA_DOMICILIO->value),
                Rule::exists('moradas', 'id')
                    ->where('utilizador_id', $this->user()?->id)
                    ->where('ativa', true),
            ],
            'tipo_documento' => ['nullable', 'string', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'prescricao_id' => ['nullable', 'uuid'],
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
            'tipo_entrega.required' => 'O tipo de entrega é obrigatório.',
            'tipo_entrega.enum' => 'O tipo de entrega deve ser RECOLHA_LOJA ou ENTREGA_DOMICILIO.',
            'morada_id.required' => 'A morada é obrigatória para entrega ao domicílio.',
            'morada_id.uuid' => 'A morada indicada não é válida.',
            'morada_id.exists' => 'A morada indicada não existe ou não está ativa.',
            'tipo_documento.max' => 'O tipo de documento não pode exceder 50 caracteres.',
            'observacoes.max' => 'As observações não podem exceder 1000 caracteres.',
            'prescricao_id.uuid' => 'A receita indicada não é válida.',
        ];
    }
}
