<?php

namespace App\Http\Requests\Catalogo;

use App\Enums\Catalogo\TipoProduto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ListarProdutosRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('pesquisa'))) {
            $this->merge(['pesquisa' => trim($this->input('pesquisa'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'pesquisa' => ['sometimes', 'string', 'max:100'],
            'categoria_id' => ['sometimes', 'uuid', Rule::exists('categorias', 'id')],
            'tipo' => ['sometimes', new Enum(TipoProduto::class)],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'categoria_id.uuid' => 'A categoria indicada não é válida.',
            'categoria_id.exists' => 'A categoria indicada não existe.',
            'tipo.enum' => 'O tipo deve ser FARMACO ou COSMETICO.',
            'por_pagina.integer' => 'O número de resultados por página deve ser um número inteiro.',
            'por_pagina.min' => 'O número de resultados por página deve ser pelo menos 1.',
            'por_pagina.max' => 'O número máximo de resultados por página é 50.',
        ];
    }
}
