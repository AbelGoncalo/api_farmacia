<?php

namespace App\Http\Requests\Prescricao;

use Illuminate\Foundation\Http\FormRequest;

class SubmeterPrescricaoRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode submeter uma prescrição.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Regras de validação do documento enviado.
     *
     * A regra `mimes` valida o conteúdo real do ficheiro (finfo), não apenas
     * a extensão do nome.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'ficheiro' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
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
            'ficheiro.required' => 'O ficheiro da receita é obrigatório.',
            'ficheiro.file' => 'O ficheiro enviado não é válido.',
            'ficheiro.mimes' => 'O ficheiro deve ser um PDF ou uma imagem (JPG ou PNG).',
            'ficheiro.max' => 'O ficheiro não pode exceder 10 MB.',
        ];
    }
}
