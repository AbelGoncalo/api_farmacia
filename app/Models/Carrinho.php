<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'utilizador_id', 'estado'])]
class Carrinho extends Model
{
    public const ESTADO_ATIVO = 'ATIVO';

    protected $table = 'carrinhos';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilizador_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemCarrinho::class, 'carrinho_id');
    }
}
