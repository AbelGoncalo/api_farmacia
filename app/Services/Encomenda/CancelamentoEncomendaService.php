<?php

namespace App\Services\Encomenda;

use App\Enums\Encomenda\EstadoEncomenda;
use App\Exceptions\EncomendaException;
use App\Models\Encomenda;
use App\Models\ItemEncomenda;
use App\Models\Produto;
use App\Models\User;
use App\Repositories\Encomenda\EncomendaRepository;
use App\Repositories\Encomenda\EntregaRepository;
use App\Repositories\Encomenda\PagamentoRepository;
use App\Repositories\Prescricao\ConsumoPrescricaoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Cancelamento de uma encomenda antes da expedição.
 *
 * O cancelamento é uma operação transacional que, para cada item da
 * encomenda: repõe o stock do produto, devolve às prescrições as
 * quantidades consumidas (pivot + contador) e marca como FALHADO
 * qualquer pagamento pendente (nunca cobrado). A entrega pendente, se
 * existir, é cancelada.
 *
 * Regras:
 * - O cliente só cancela as próprias encomendas ainda AGUARDANDO_PAGAMENTO.
 * - Um operador (gate `processar-encomendas`) pode cancelar qualquer
 *   encomenda AGUARDANDO_PAGAMENTO ou PAGA; encomendas EM_PREPARACAO,
 *   ENVIADA ou ENTREGUE não podem ser canceladas por esta rota (fluxo
 *   de devolução/estorno é outro).
 *
 * A IA não está envolvida: o cancelamento é sempre uma decisão humana.
 */
class CancelamentoEncomendaService
{
    public function __construct(
        private readonly EncomendaRepository $encomendaRepository,
        private readonly PagamentoRepository $pagamentoRepository,
        private readonly EntregaRepository $entregaRepository,
        private readonly ConsumoPrescricaoRepository $consumoPrescricaoRepository,
    ) {}

    /**
     * Cancela uma encomenda: repõe stock, devolve quantidades às prescrições,
     * falha pagamentos pendentes e cancela a entrega pendente.
     *
     * O cliente só cancela as próprias encomendas AGUARDANDO_PAGAMENTO;
     * um operador (gate `processar-encomendas`) cancela AGUARDANDO_PAGAMENTO
     * ou PAGA de qualquer cliente.
     *
     * @throws EncomendaException quando a encomenda não existe, não pertence
     *                            ao utilizador ou não está num estado cancelável.
     */
    public function cancelar(string $encomendaId, User $utilizador): Encomenda
    {
        return DB::transaction(function () use ($encomendaId, $utilizador): Encomenda {
            $eOperador = Gate::forUser($utilizador)->allows('processar-encomendas');

            $encomenda = $eOperador
                ? $this->encomendaRepository->travar($encomendaId)
                : $this->encomendaRepository->travarDoUtilizador($encomendaId, $utilizador->id);

            if ($encomenda === null) {
                throw new EncomendaException('Encomenda não encontrada.', 404);
            }

            $this->garantirCancelavel($encomenda, $eOperador);

            $itens = ItemEncomenda::query()
                ->where('encomenda_id', $encomenda->id)
                ->lockForUpdate()
                ->get();

            foreach ($itens as $item) {
                $produto = $item->produto;

                if ($produto !== null) {
                    $this->encomendaRepository->reporStock($produto, (float) $item->quantidade);
                }

                $this->devolverConsumoPrescricao($item);
            }

            $this->pagamentoRepository->falharPendentesDaEncomenda($encomenda->id);
            $this->entregaRepository->cancelarPendenteDaEncomenda($encomenda->id);

            $this->encomendaRepository->alterarEstado($encomenda, EstadoEncomenda::CANCELADA);

            return $encomenda->fresh('itens.produto:id,codigo,nome');
        });
    }

    /**
     * Garante que a encomenda está num estado que permite cancelamento.
     *
     * Não-operador: só AGUARDANDO_PAGAMENTO (o cliente desfaz a própria
     * compra antes de a pagar). Operador: AGUARDANDO_PAGAMENTO ou PAGA
     * (cancelamento administrativo, com estorno de stock e prescrição).
     * EM_PREPARACAO, ENVIADA e ENTREGUE não são canceláveis por esta rota
     * (fluxo de devolução/estorno é outro).
     *
     * @throws EncomendaException
     */
    private function garantirCancelavel(Encomenda $encomenda, bool $eOperador): void
    {
        $estadosCancelaveis = $eOperador
            ? [EstadoEncomenda::AGUARDANDO_PAGAMENTO->value, EstadoEncomenda::PAGA->value]
            : [EstadoEncomenda::AGUARDANDO_PAGAMENTO->value];

        if (! in_array($encomenda->estado, $estadosCancelaveis, true)) {
            throw new EncomendaException(
                'A encomenda não pode ser cancelada no estado atual.',
                409,
                ['estado' => ['A encomenda não pode ser cancelada no estado atual.']],
            );
        }
    }

    /**
     * Devolve às prescrições as quantidades que este item de encomenda consumiu.
     */
    private function devolverConsumoPrescricao(ItemEncomenda $item): void
    {
        $itensPrescricao = $item->itensPrescricao;

        foreach ($itensPrescricao as $itemPrescricao) {
            $this->consumoPrescricaoRepository->devolverConsumo($itemPrescricao, $item);
        }
    }
}
