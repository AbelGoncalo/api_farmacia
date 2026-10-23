<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id',
    'utilizador_id',
    'caminho_ficheiro',
    'tipo_ficheiro',
    'estado',
    'resultado_ia',
    'mensagem_erro',
    'data_emissao',
    'validade_ate',
    'notas_profissional',
    'revisto_por',
    'revisto_em',
])]
class Prescricao extends Model
{
    use HasFactory;

    protected $table = 'prescricoes';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'resultado_ia' => 'array',
            'data_emissao' => 'date',
            'validade_ate' => 'date',
            'revisto_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, Prescricao> */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilizador_id');
    }

    /** @return BelongsTo<User, Prescricao> */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisto_por');
    }

    /** @return HasMany<ItemPrescricao, Prescricao> */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemPrescricao::class, 'prescricao_id');
    }
}
