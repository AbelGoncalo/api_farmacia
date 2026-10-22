<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'encomenda_id',
    'tipo',
    'estado',
    'morada_historico',
    'levantamento_confirmado_em',
    'entregue_em',
])]
class Entrega extends Model
{
    protected $table = 'entregas';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'morada_historico' => 'array',
            'levantamento_confirmado_em' => 'datetime',
            'entregue_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Encomenda, Entrega> */
    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(Encomenda::class, 'encomenda_id');
    }
}
