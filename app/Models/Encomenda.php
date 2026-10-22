<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'id',
    'numero_encomenda',
    'utilizador_id',
    'morada_id',
    'tipo_entrega',
    'estado',
    'subtotal',
    'taxa_entrega',
    'total',
    'tipo_documento',
    'observacoes',
])]
class Encomenda extends Model
{
    protected $table = 'encomendas';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'taxa_entrega' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, Encomenda> */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilizador_id');
    }

    /** @return HasMany<ItemEncomenda, Encomenda> */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemEncomenda::class, 'encomenda_id');
    }

    /** @return HasMany<Pagamento, Encomenda> */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'encomenda_id');
    }

    /** @return HasOne<Entrega, Encomenda> */
    public function entrega(): HasOne
    {
        return $this->hasOne(Entrega::class, 'encomenda_id');
    }

    /** @return BelongsTo<Morada, Encomenda> */
    public function morada(): BelongsTo
    {
        return $this->belongsTo(Morada::class, 'morada_id');
    }
}
