<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_produto_primavera',
    'codigo',
    'tipo',
    'nome',
    'categoria_id',
    'preco',
    'quantidade_stock',
    'unidade',
    'taxa_iva',
    'url_imagem',
    'requer_prescricao',
    'disponivel_online',
    'ativo',
    'sincronizado_em',
])]
class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'quantidade_stock' => 'decimal:3',
            'taxa_iva' => 'decimal:2',
            'requer_prescricao' => 'boolean',
            'disponivel_online' => 'boolean',
            'ativo' => 'boolean',
            'sincronizado_em' => 'datetime',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
