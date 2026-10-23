<?php

namespace App\Services\Encomenda;

use App\Enums\Encomenda\EstadoEncomenda;
use App\Enums\Encomenda\TipoEntrega;
use App\Exceptions\EncomendaException;
use App\Models\Encomenda;
use App\Models\User;
use App\Repositories\Carrinho\CarrinhoRepository;
use App\Repositories\Encomenda\EncomendaRepository;
use App\Services\Prescricao\ConsumoPrescricaoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EncomendaService
{
    public function __construct(
        private readonly EncomendaRepository $encomendaRepository,
        private readonly CarrinhoRepository $carrinhoRepository,
        private readonly ConsumoPrescricaoService $consumoPrescricaoService,
    ) {}

    /**
     * Converte o carrinho ativo do utilizador numa encomenda.
     *
     * Dentro de uma única transação: trava o stock, revalida disponibilidade,
     * calcula os totais (com a taxa de entrega), cria a encomenda e os seus itens
     * (guardando o instantâneo de código/nome/preço), decrementa o stock e esvazia
     * o carrinho. O preço é recalculado com o valor atual do produto.
     *
     * @param  array{tipo_entrega: string, morada_id?: ?string, tipo_documento?: ?string, observacoes?: ?string}  $dados
     *
     * @throws EncomendaException quando o carrinho está vazio ou o stock é insuficiente.
     */
    public function criar(User $utilizador, array $dados): Encomenda
    {
        return DB::transaction(function () use ($utilizador, $dados): Encomenda {
            $carrinho = $this->carrinhoRepository->buscarCarrinhoAtivoParaCheckout($utilizador->id);

            if ($carrinho === null || $carrinho->itens->isEmpty()) {
                throw new EncomendaException(
                    'O carrinho está vazio.',
                    422,
                    ['carrinho' => ['O carrinho está vazio.']],
                );
            }

            $tipoEntrega = TipoEntrega::from($dados['tipo_entrega']);
            $produtos = $this->encomendaRepository->travarProdutos(
                $carrinho->itens->pluck('produto_id')->all(),
            );

            $subtotal = 0.0;
            $linhas = [];

            foreach ($carrinho->itens as $item) {
                $produto = $produtos->get($item->produto_id);

                if ($produto === null || ! $produto->ativo || ! $produto->disponivel_online) {
                    throw new EncomendaException(
                        'O produto indicado não está disponível.',
                        409,
                        ['produto_id' => ['O produto indicado não está disponível.']],
                    );
                }

                $quantidade = (float) $item->quantidade;

                if ($quantidade > (float) $produto->quantidade_stock) {
                    throw new EncomendaException(
                        'Quantidade pedida superior ao stock disponível.',
                        409,
                        ['quantidade' => ['Quantidade pedida superior ao stock disponível.']],
                    );
                }

                $precoUnitario = (float) $produto->preco;
                $subtotalItem = round($quantidade * $precoUnitario, 2);
                $subtotal += $subtotalItem;

                $linhas[] = [
                    'produto' => $produto,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'subtotal' => $subtotalItem,
                ];
            }

            $prescricao = $this->consumoPrescricaoService->validar(
                $utilizador->id,
                $dados['prescricao_id'] ?? null,
                $produtos,
                $linhas,
            );

            $subtotal = round($subtotal, 2);
            $taxaEntrega = $this->taxaEntrega($tipoEntrega);
            $itensEncomenda = new Collection;
            $total = round($subtotal + $taxaEntrega, 2);

            $encomenda = $this->encomendaRepository->criarEncomenda([
                'id' => (string) Str::uuid(),
                'numero_encomenda' => $this->gerarNumeroEncomenda(),
                'utilizador_id' => $utilizador->id,
                'morada_id' => $dados['morada_id'] ?? null,
                'tipo_entrega' => $tipoEntrega->value,
                'estado' => EstadoEncomenda::AGUARDANDO_PAGAMENTO->value,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxaEntrega,
                'total' => $total,
                'tipo_documento' => $dados['tipo_documento'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            foreach ($linhas as $linha) {
                $itemEncomenda = $this->encomendaRepository->criarItem(
                    $encomenda,
                    $linha['produto'],
                    $linha['quantidade'],
                    $linha['preco_unitario'],
                    $linha['subtotal'],
                );

                $itensEncomenda->put($linha['produto']->id, $itemEncomenda);

                $this->encomendaRepository->decrementarStock($linha['produto'], $linha['quantidade']);
            }

            if ($prescricao !== null) {
                $this->consumoPrescricaoService->registar(
                    $prescricao,
                    $this->consumoPrescricaoService->itensPorProduto($prescricao),
                    $itensEncomenda,
                );
            }

            $this->carrinhoRepository->esvaziarCarrinho($carrinho);

            return $this->encomendaRepository->buscarDoUtilizador($encomenda->id, $utilizador->id);
        });
    }

    /**
     * Lista as encomendas do utilizador, de forma paginada.
     */
    public function listar(User $utilizador, int $porPagina): LengthAwarePaginator
    {
        return $this->encomendaRepository->listarPorUtilizador($utilizador->id, $porPagina);
    }

    /**
     * Procura uma encomenda do utilizador, ou null caso não pertença a ele.
     */
    public function buscar(User $utilizador, string $id): ?Encomenda
    {
        return $this->encomendaRepository->buscarDoUtilizador($id, $utilizador->id);
    }

    /**
     * Calcula a taxa de entrega consoante o tipo de entrega escolhido.
     *
     * Recolha na loja não tem taxa; entrega ao domicílio usa o valor de configuração.
     */
    private function taxaEntrega(TipoEntrega $tipo): float
    {
        return match ($tipo) {
            TipoEntrega::RECOLHA_LOJA => 0.0,
            TipoEntrega::ENTREGA_DOMICILIO => (float) config('encomendas.taxa_entrega_domicilio', 0.0),
        };
    }

    /**
     * Gera um número de encomenda único e legível (ENC-AAAAMMDD-XXXXXXXX).
     */
    private function gerarNumeroEncomenda(): string
    {
        return sprintf('ENC-%s-%s', now()->format('Ymd'), strtoupper(Str::random(8)));
    }
}
