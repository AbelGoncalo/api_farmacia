<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'nome', 'descricao', 'ativa'])]
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
