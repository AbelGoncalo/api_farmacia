<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'id',
    'prescricao_id',
    'texto_original',
    'nome_medicamento',
    'dosagem',
    'apresentacao',
    'quantidade',
    'produto_id',
    'estado_correspondencia',
    'metodo_correspondencia',
    'confianca_correspondencia',
])]
class ItemPrescricao extends Model
{
    use HasFactory;

    protected $table = 'itens_prescricao';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'confianca_correspondencia' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Prescricao, ItemPrescricao> */
    public function prescricao(): BelongsTo
    {
        return $this->belongsTo(Prescricao::class, 'prescricao_id');
    }

    /**
     * Produto do catálogo correspondente. Pode ser null (sem correspondência)
     * ou passar a null se o produto for eliminado (nullOnDelete).
     *
     * @return BelongsTo<Produto, ItemPrescricao>
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Itens de encomenda que consumiram quantidades deste item prescrito,
     * através do pivot `itens_encomenda_prescricao` (registos de utilização).
     *
     * @return BelongsToMany<ItemEncomenda, ItemPrescricao>
     */
    public function itensEncomenda(): BelongsToMany
    {
        return $this->belongsToMany(
            ItemEncomenda::class,
            'itens_encomenda_prescricao',
            'item_prescricao_id',
            'item_encomenda_id',
        )->withPivot('quantidade');
    }
}
