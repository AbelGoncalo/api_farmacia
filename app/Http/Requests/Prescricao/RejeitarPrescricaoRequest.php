<?php

namespace App\Http\Requests\Prescricao;

use Illuminate\Foundation\Http\FormRequest;

class RejeitarPrescricaoRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {prescricao} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['prescricao' => $this->route('prescricao')]);
    }

    /**
     * Determina se o utilizador autenticado pode rejeitar prescrições.
     *
     * A autorização é reforçada no middleware `can:rever-prescricoes` da rota;
     * aqui garante-se apenas que existe utilizador autenticado.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação da rejeição.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'prescricao' => ['required', 'uuid'],
            'motivo' => ['required', 'string', 'max:2000'],
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
            'motivo.required' => 'O motivo da rejeição é obrigatório.',
            'motivo.string' => 'O motivo da rejeição deve ser um texto.',
            'motivo.max' => 'O motivo da rejeição não pode exceder 2000 caracteres.',
        ];
    }
}
