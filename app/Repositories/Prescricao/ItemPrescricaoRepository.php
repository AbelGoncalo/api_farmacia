<?php

namespace App\Repositories\Prescricao;

use App\Models\ItemPrescricao;
use Illuminate\Database\Eloquent\Collection;

class ItemPrescricaoRepository
{
    /**
     * Cria um item (linha) de uma prescrição a partir dos dados extraídos.
     *
     * @param  array{id: string, prescricao_id: string, texto_original: string, nome_medicamento: string, dosagem?: ?string, apresentacao?: ?string, quantidade?: ?float, produto_id?: ?string, estado_correspondencia: string, metodo_correspondencia?: ?string, confianca_correspondencia?: ?float}  $dados
     */
    public function criarItem(array $dados): ItemPrescricao
    {
        $item = new ItemPrescricao;
        $item->id = $dados['id'];
        $item->prescricao_id = $dados['prescricao_id'];
        $item->texto_original = $dados['texto_original'];
        $item->nome_medicamento = $dados['nome_medicamento'];
        $item->dosagem = $dados['dosagem'] ?? null;
        $item->apresentacao = $dados['apresentacao'] ?? null;
        $item->quantidade = $dados['quantidade'] ?? null;
        $item->produto_id = $dados['produto_id'] ?? null;
        $item->estado_correspondencia = $dados['estado_correspondencia'];
        $item->metodo_correspondencia = $dados['metodo_correspondencia'] ?? null;
        $item->confianca_correspondencia = $dados['confianca_correspondencia'] ?? null;
        $item->save();

        return $item;
    }

    /**
     * Lista os itens de uma prescrição, com o produto correspondente carregado.
     *
     * @return Collection<string, ItemPrescricao>
     */
    public function listarPorPrescricao(string $prescricaoId): Collection
    {
        return ItemPrescricao::query()
            ->with('produto:id,codigo,nome')
            ->where('prescricao_id', $prescricaoId)
            ->get();
    }
}
