<?php

namespace App\Repositories\Catalogo;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogoRepository
{
    /** @return Collection<int, Categoria> */
    public function listarCategoriasComProdutos(
        bool $categoriasAtivas,
        bool $produtosAtivos,
        bool $disponiveisOnline,
        float $stockMinimo,
    ): Collection {
        return Categoria::query()
            ->where('ativa', $categoriasAtivas)
            ->whereHas('produtos', fn ($query) => $query
                ->where('ativo', $produtosAtivos)
                ->where('disponivel_online', $disponiveisOnline)
                ->where('quantidade_stock', '>', $stockMinimo))
            ->orderBy('nome')
            ->get();
    }

    /**
     * @param  array{pesquisa?: string, categoria_id?: string, tipo?: string}  $filtros
     * @return LengthAwarePaginator<int, Produto>
     */
    public function listarProdutos(
        array $filtros,
        int $porPagina,
        bool $ativo,
        bool $disponivelOnline,
        float $stockMinimo,
    ): LengthAwarePaginator {
        return Produto::query()
            ->with(['categoria:id,nome'])
            ->where('ativo', $ativo)
            ->where('disponivel_online', $disponivelOnline)
            ->where('quantidade_stock', '>', $stockMinimo)
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['categoria_id'] ?? null, fn ($query, $categoriaId) => $query->where('categoria_id', $categoriaId))
            ->when($filtros['pesquisa'] ?? null, function ($query, $pesquisa): void {
                $query->where(function ($query) use ($pesquisa): void {
                    $query->where('nome', 'like', "%{$pesquisa}%")
                        ->orWhere('codigo', 'like', "%{$pesquisa}%");
                });
            })
            ->orderBy('nome')
            ->paginate($porPagina);
    }

    /**
     * Produtos ativos candidatos à correspondência de um item de prescrição.
     *
     * @param  string  $palavraChave  palavra-chave normalizada (sem acentos, minúsculas)
     * @return Collection<int, Produto>
     */
    public function candidatosCorrespondencia(string $palavraChave): Collection
    {
        $escapada = addcslashes($palavraChave, '%_\\');

        return Produto::query()
            ->where('ativo', true)
            ->where('nome', 'like', "%{$escapada}%")
            ->orderBy('nome')
            ->limit(50)
            ->get(['id', 'codigo', 'nome']);
    }

    public function buscarProduto(
        string $id,
        bool $ativo,
        bool $disponivelOnline,
        float $stockMinimo,
    ): ?Produto {
        return Produto::query()
            ->with(['categoria:id,nome'])
            ->where('ativo', $ativo)
            ->where('disponivel_online', $disponivelOnline)
            ->where('quantidade_stock', '>', $stockMinimo)
            ->find($id);
    }
}
