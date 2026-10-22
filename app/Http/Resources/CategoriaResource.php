<?php

namespace App\Http\Resources;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Categoria */
class CategoriaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Categoria $categoria */
        $categoria = $this->resource;

        return [
            'id' => $categoria->id,
            'nome' => $categoria->nome,
            'descricao' => $categoria->descricao,
        ];
    }
}
