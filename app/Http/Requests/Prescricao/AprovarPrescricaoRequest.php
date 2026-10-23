<?php

namespace App\Http\Requests\Prescricao;

use Illuminate\Foundation\Http\FormRequest;

class AprovarPrescricaoRequest extends FormRequest
{
    /**
     * Injeta o parâmetro de rota {prescricao} nos dados de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['prescricao' => $this->route('prescricao')]);
    }

    /**
     * Determina se o utilizador autenticado pode aprovar prescrições.
     *
     * A autorização é reforçada no middleware `can:rever-prescricoes` da rota;
     * aqui garante-se apenas que existe utilizador autenticado.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação da aprovação.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'prescricao' => ['required', 'uuid'],
            'notas_profissional' => ['nullable', 'string', 'max:2000'],
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
            'notas_profissional.string' => 'As notas do profissional devem ser um texto.',
            'notas_profissional.max' => 'As notas do profissional não podem exceder 2000 caracteres.',
        ];
    }
}
