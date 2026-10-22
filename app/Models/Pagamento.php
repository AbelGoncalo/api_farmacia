<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'encomenda_id',
    'metodo',
    'estado',
    'id_transacao',
    'referencia',
    'valor',
    'pago_em',
    'dados_resposta_operador',
])]
class Pagamento extends Model
{
    protected $table = 'pagamentos';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'pago_em' => 'datetime',
            'dados_resposta_operador' => 'array',
        ];
    }

    /** @return BelongsTo<Encomenda, Pagamento> */
    public function encomenda(): BelongsTo
    {
        return $this->belongsTo(Encomenda::class, 'encomenda_id');
    }
}
