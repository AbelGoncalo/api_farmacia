<?php

namespace App\Repositories\Encomenda;

use App\Models\Encomenda;
use App\Models\ItemEncomenda;
use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class EncomendaRepository
{
    /**
     * Cria o registo de uma encomenda a partir dos dados já calculados pelo serviço.
     *
     * @param  array{id: string, numero_encomenda: string, utilizador_id: int, morada_id?: ?string, tipo_entrega: string, estado: string, subtotal: float, taxa_entrega: float, total: float, tipo_documento?: ?string, observacoes?: ?string}  $dados
     */
    public function criarEncomenda(array $dados): Encomenda
    {
        $encomenda = new Encomenda;
        $encomenda->id = $dados['id'];
        $encomenda->numero_encomenda = $dados['numero_encomenda'];
        $encomenda->utilizador_id = $dados['utilizador_id'];
        $encomenda->morada_id = $dados['morada_id'] ?? null;
        $encomenda->tipo_entrega = $dados['tipo_entrega'];
        $encomenda->estado = $dados['estado'];
        $encomenda->subtotal = $dados['subtotal'];
        $encomenda->taxa_entrega = $dados['taxa_entrega'];
        $encomenda->total = $dados['total'];
        $encomenda->tipo_documento = $dados['tipo_documento'] ?? null;
        $encomenda->observacoes = $dados['observacoes'] ?? null;
        $encomenda->save();

        return $encomenda;
    }

    /**
     * Cria um item (linha) de uma encomenda, guardando o instantâneo do produto.
     */
    public function criarItem(
        Encomenda $encomenda,
        Produto $produto,
        float $quantidade,
        float $precoUnitario,
        float $subtotal,
    ): ItemEncomenda {
        $item = new ItemEncomenda;
        $item->id = (string) Str::uuid();
        $item->encomenda_id = $encomenda->id;
        $item->produto_id = $produto->id;
        $item->codigo_produto_historico = $produto->codigo;
        $item->nome_produto_historico = $produto->nome;
        $item->quantidade = $quantidade;
        $item->preco_unitario = $precoUnitario;
        $item->subtotal = $subtotal;
        $item->save();

        return $item;
    }

    /**
     * Trava as linhas dos produtos indicados (SELECT ... FOR UPDATE) para serializar
     * a validação de stock em transações concorrentes.
     *
     * @param  list<string>  $ids
     * @return Collection<string, Produto>
     */
    public function travarProdutos(array $ids): Collection
    {
        return Produto::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * Decrementa a quantidade em stock do produto indicado.
     */
    public function decrementarStock(Produto $produto, float $quantidade): void
    {
        $produto->decrement('quantidade_stock', $quantidade);
    }

    /**
     * Lista as encomendas do utilizador, com itens e produtos, de forma paginada.
     */
    public function listarPorUtilizador(int $utilizadorId, int $porPagina): LengthAwarePaginator
    {
        return Encomenda::query()
            ->with('itens.produto:id,codigo,nome')
            ->where('utilizador_id', $utilizadorId)
            ->orderByDesc('criado_em')
            ->paginate($porPagina);
    }

    /**
     * Procura uma encomenda pertencente ao utilizador, ou null caso não exista.
     */
    public function buscarDoUtilizador(string $id, int $utilizadorId): ?Encomenda
    {
        return Encomenda::query()
            ->with('itens.produto:id,codigo,nome')
            ->where('id', $id)
            ->where('utilizador_id', $utilizadorId)
            ->first();
    }
}
