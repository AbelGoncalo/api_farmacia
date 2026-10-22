<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'carrinho_id', 'produto_id', 'quantidade', 'preco_unitario'])]
class ItemCarrinho extends Model
{
    protected $table = 'itens_carrinho';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'preco_unitario' => 'decimal:2',
        ];
    }

    public function carrinho(): BelongsTo
    {
        return $this->belongsTo(Carrinho::class, 'carrinho_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
