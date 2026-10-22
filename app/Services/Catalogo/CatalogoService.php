<?php

namespace App\Services\Catalogo;

use App\Enums\Catalogo\TipoProduto;
use App\Models\Categoria;
use App\Models\Produto;
use App\Repositories\Catalogo\CatalogoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogoService
{
    private const STOCK_MINIMO_DISPONIVEL = 0.0;

    public function __construct(
        private readonly CatalogoRepository $catalogoRepository,
    ) {}

    /** @return Collection<int, Categoria> */
    public function listarCategorias(): Collection
    {
        return $this->catalogoRepository->listarCategoriasComProdutos(
            categoriasAtivas: true,
            produtosAtivos: true,
            disponiveisOnline: true,
            stockMinimo: self::STOCK_MINIMO_DISPONIVEL,
        );
    }

    /**
     * @param  array{pesquisa?: string, categoria_id?: string, tipo?: string}  $filtros
     * @return LengthAwarePaginator<int, Produto>
     */
    public function listarProdutos(array $filtros, int $porPagina): LengthAwarePaginator
    {
        if (isset($filtros['tipo'])) {
            $filtros['tipo'] = TipoProduto::from($filtros['tipo'])->value;
        }

        return $this->catalogoRepository->listarProdutos(
            filtros: $filtros,
            porPagina: $porPagina,
            ativo: true,
            disponivelOnline: true,
            stockMinimo: self::STOCK_MINIMO_DISPONIVEL,
        );
    }

    public function buscarProduto(string $id): ?Produto
    {
        return $this->catalogoRepository->buscarProduto(
            id: $id,
            ativo: true,
            disponivelOnline: true,
            stockMinimo: self::STOCK_MINIMO_DISPONIVEL,
        );
    }
}
