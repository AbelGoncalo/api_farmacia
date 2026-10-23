<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'id',
    'encomenda_id',
    'produto_id',
    'codigo_produto_historico',
    'nome_produto_historico',
    'quantidade',
    'preco_unitario',
    'subtotal',
])]
class ItemEncomenda extends Model
{
    protected $table = 'itens_encomenda';

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
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Encomenda, ItemEncomenda> */
    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(Encomenda::class, 'encomenda_id');
    }

    /** @return BelongsTo<Produto, ItemEncomenda> */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Itens de prescrição que a encomenda consumiu, através do pivot
     * `itens_encomenda_prescricao` (registos de utilização).
     *
     * @return BelongsToMany<ItemPrescricao, ItemEncomenda>
     */
    public function itensPrescricao(): BelongsToMany
    {
        return $this->belongsToMany(
            ItemPrescricao::class,
            'itens_encomenda_prescricao',
            'item_encomenda_id',
            'item_prescricao_id',
        )->withPivot('quantidade');
    }
}
