<?php

namespace App\Services\Prescricao;

use App\Exceptions\EncomendaException;
use App\Models\ItemEncomenda;
use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Repositories\Prescricao\ConsumoPrescricaoRepository;
use Illuminate\Support\Collection;

/**
 * Aplica a regra de receita na compra e o consumo progressivo da quantidade
 * prescrita (decisão 3-A).
 *
 * Um produto com `requer_prescricao` só pode ser comprado com uma receita
 * APROVADA do próprio utilizador, e a quantidade pedida nunca pode exceder o
 * que resta daquele item da receita. A IA nunca aprova nada: este serviço só
 * consome receitas que um profissional já aprovou manualmente (Fase 5).
 *
 * O consumo corre dentro da transação do checkout: os itens da receita são
 * travados (SELECT ... FOR UPDATE) e o registo de utilização fica no pivot
 * `itens_encomenda_prescricao`.
 */
class ConsumoPrescricaoService
{
    public function __construct(
        private readonly ConsumoPrescricaoRepository $consumoRepository,
    ) {}

    /**
     * Valida, antes de criar a encomenda, que todos os produtos que exigem
     * receita são cobertos pela receita aprovada indicada e pela quantidade
     * que resta de cada item.
     *
     * Lança exceção (rollback da transação) quando a receita é inválida,
     * expirada ou não cobre a quantidade pedida.
     *
     * @param  Collection<string, Produto>  $produtos  indexados por id
     * @param  list<array{produto: Produto, quantidade: float}>  $linhas
     *
     * @throws EncomendaException
     */
    public function validar(int $utilizadorId, ?string $prescricaoId, Collection $produtos, array $linhas): ?Prescricao
    {
        $exigemReceita = array_values(array_filter(
            $linhas,
            fn (array $linha): bool => (bool) $linha['produto']->requer_prescricao,
        ));

        if ($exigemReceita === []) {
            return null;
        }

        if ($prescricaoId === null) {
            throw new EncomendaException(
                'Este pedido contém medicamentos que exigem receita. Indique uma receita aprovada (prescricao_id).',
                422,
                ['prescricao_id' => ['É obrigatório indicar uma receita aprovada para medicamentos sujeitos a receita.']],
            );
        }

        $prescricao = $this->consumoRepository->travarAprovadaDoUtilizador($prescricaoId, $utilizadorId);

        if ($prescricao === null) {
            throw new EncomendaException(
                'A receita indicada não é válida ou não está aprovada.',
                409,
                ['prescricao_id' => ['A receita indicada não é válida ou não está aprovada.']],
            );
        }

        if ($this->estaExpirada($prescricao)) {
            throw new EncomendaException(
                'A receita indicada está expirada.',
                409,
                ['prescricao_id' => ['A receita indicada está expirada.']],
            );
        }

        $itensPorProduto = $this->consumoRepository->itensPorProduto($prescricao);

        foreach ($exigemReceita as $linha) {
            /** @var Produto $produto */
            $produto = $linha['produto'];
            $quantidade = (float) $linha['quantidade'];

            $item = $itensPorProduto->get($produto->id);

            if ($item === null) {
                throw new EncomendaException(
                    'A receita indicada não cobre o produto "'.$produto->nome.'".',
                    409,
                    ['prescricao_id' => ['A receita indicada não cobre todos os medicamentos sujeitos a receita.']],
                );
            }

            // Um item de receita sem quantidade definida não impõe limite de consumo.
            if ($item->quantidade === null) {
                continue;
            }

            if ($quantidade > (float) $item->quantidade_restante) {
                throw new EncomendaException(
                    'A quantidade pedida de "'.$produto->nome.'" excede o que resta da receita.',
                    409,
                    ['prescricao_id' => ['A quantidade pedida excede a quantidade disponível na receita.']],
                );
            }
        }

        return $prescricao;
    }

    /**
     * Regista o consumo das quantidades após os itens de encomenda existirem,
     * casando cada linha que exige receita com o item de receita correspondente.
     *
     * @param  Collection<string, ItemPrescricao>  $itensPorProduto  indexados por produto
     * @param  Collection<string, ItemEncomenda>  $itensEncomenda  indexados por produto
     */
    public function registar(Prescricao $prescricao, Collection $itensPorProduto, Collection $itensEncomenda): void
    {
        foreach ($itensEncomenda as $produtoId => $itemEncomenda) {
            $item = $itensPorProduto->get($produtoId);

            if ($item === null) {
                continue;
            }

            $this->consumoRepository->registarConsumo($item, $itemEncomenda, (float) $itemEncomenda->quantidade);
        }
    }

    /**
     * Itens de receita com produto correspondente, indexados por produto.
     *
     * @return Collection<string, ItemPrescricao>
     */
    public function itensPorProduto(Prescricao $prescricao): Collection
    {
        return $this->consumoRepository->itensPorProduto($prescricao);
    }

    /**
     * Uma receita está expirada quando tem validade e essa data já passou.
     */
    private function estaExpirada(Prescricao $prescricao): bool
    {
        return $prescricao->validade_ate !== null && $prescricao->validade_ate->isPast();
    }
}
