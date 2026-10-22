<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'utilizador_id',
    'etiqueta',
    'nome_destinatario',
    'telefone',
    'provincia',
    'municipio',
    'bairro',
    'rua',
    'numero_porta',
    'referencia',
    'predefinida',
    'ativa',
])]
class Morada extends Model
{
    protected $table = 'moradas';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'predefinida' => 'boolean',
            'ativa' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, Morada> */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilizador_id');
    }
}
