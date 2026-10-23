<?php

namespace App\Repositories\Prescricao;

use App\Models\ItemEncomenda;
use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use Illuminate\Database\Eloquent\Collection;

class ConsumoPrescricaoRepository
{
    /**
     * Procura uma prescrição aprovada do utilizador, com os itens travados
     * para serializar o consumo em transações concorrentes, ou null quando
     * não existe, não pertence ao utilizador ou não está aprovada.
     */
    public function travarAprovadaDoUtilizador(string $id, int $utilizadorId): ?Prescricao
    {
        $prescricao = Prescricao::query()
            ->where('id', $id)
            ->where('utilizador_id', $utilizadorId)
            ->where('estado', 'APROVADA')
            ->lockForUpdate()
            ->first();

        if ($prescricao === null) {
            return null;
        }

        $prescricao->setRelation(
            'itens',
            ItemPrescricao::query()
                ->where('prescricao_id', $prescricao->id)
                ->lockForUpdate()
                ->get(),
        );

        return $prescricao;
    }

    /**
     * Itens da prescrição que têm produto correspondente, indexados por
     * produto, para casar com as linhas de encomenda que exigem receita.
     *
     * @return Collection<string, ItemPrescricao>
     */
    public function itensPorProduto(Prescricao $prescricao): Collection
    {
        return $prescricao->itens
            ->filter(fn (ItemPrescricao $item): bool => $item->produto_id !== null)
            ->keyBy('produto_id');
    }

    /**
     * Regista o consumo de uma quantidade de um item de prescrição por um
     * item de encomenda, através do pivot, e incrementa o contador derivado.
     *
     * Corre dentro da transação do checkout: o pivot é a fonte de verdade
     * e o contador só agrega para consulta rápida do que resta.
     */
    public function registarConsumo(ItemPrescricao $item, ItemEncomenda $itemEncomenda, float $quantidade): void
    {
        $item->itensEncomenda()->attach($itemEncomenda->id, [
            'quantidade' => $quantidade,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        $item->quantidade_consumida = (float) $item->quantidade_consumida + $quantidade;
        $item->save();
    }
}
