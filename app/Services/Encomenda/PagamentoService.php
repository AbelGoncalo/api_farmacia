<?php

namespace App\Services\Encomenda;

use App\Enums\Encomenda\EstadoEncomenda;
use App\Enums\Encomenda\EstadoPagamento;
use App\Exceptions\EncomendaException;
use App\Models\Encomenda;
use App\Models\Pagamento;
use App\Models\User;
use App\Repositories\Encomenda\EncomendaRepository;
use App\Repositories\Encomenda\PagamentoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagamentoService
{
    public function __construct(
        private readonly PagamentoRepository $pagamentoRepository,
        private readonly EncomendaRepository $encomendaRepository,
        private readonly EntregaService $entregaService,
    ) {}

    /**
     * Regista um pagamento pendente para uma encomenda do utilizador.
     *
     * Dentro de uma transação: trava a encomenda, valida que ela ainda aguarda
     * pagamento e que não existe outro pagamento pendente, e cria o registo com
     * o valor total da encomenda (calculado no servidor, nunca pelo cliente).
     *
     * @param  array{metodo: string, referencia?: ?string}  $dados
     * @return ?Pagamento o pagamento criado, ou null se a encomenda não pertencer ao utilizador.
     *
     * @throws EncomendaException quando a encomenda já foi paga/cancelada ou já
     *                            existe um pagamento pendente.
     */
    public function registar(User $utilizador, string $encomendaId, array $dados): ?Pagamento
    {
        return DB::transaction(function () use ($utilizador, $encomendaId, $dados): ?Pagamento {
            $encomenda = $this->encomendaRepository->travarDoUtilizador($encomendaId, $utilizador->id);

            if ($encomenda === null) {
                return null;
            }

            $this->garantirEncomendaAguardandoPagamento($encomenda);

            $pendente = $this->pagamentoRepository->buscarPendenteDaEncomenda($encomenda->id);

            if ($pendente !== null) {
                throw new EncomendaException(
                    'Já existe um pagamento pendente para esta encomenda.',
                    409,
                    ['pagamento' => ['Já existe um pagamento pendente para esta encomenda.']],
                );
            }

            return $this->pagamentoRepository->criarPagamento([
                'id' => (string) Str::uuid(),
                'encomenda_id' => $encomenda->id,
                'metodo' => $dados['metodo'],
                'estado' => EstadoPagamento::PENDENTE->value,
                'valor' => (float) $encomenda->total,
                'referencia' => $dados['referencia'] ?? null,
            ]);
        });
    }

    /**
     * Confirma um pagamento pendente, move a encomenda associada para PAGA
     * e cria a entrega correspondente ao tipo escolhido.
     *
     * Dentro de uma transação: trava o pagamento e a encomenda, valida que ambos
     * estão em estado válido, confirma o pagamento (com o id de transação e a
     * resposta do operador, se fornecidos), atualiza o estado da encomenda e
     * cria o registo de entrega (com instantâneo da morada, se ao domicílio).
     *
     * @param  array{id_transacao?: ?string, dados_resposta_operador?: ?array}  $dados
     * @return ?Pagamento o pagamento confirmado, ou null se não existir.
     *
     * @throws EncomendaException quando o pagamento já foi processado ou a encomenda
     *                            não aguarda mais pagamento.
     */
    public function confirmar(string $pagamentoId, array $dados): ?Pagamento
    {
        return DB::transaction(function () use ($pagamentoId, $dados): ?Pagamento {
            $pagamento = $this->pagamentoRepository->travarPagamento($pagamentoId);

            if ($pagamento === null) {
                return null;
            }

            $encomenda = $this->encomendaRepository->travar($pagamento->encomenda_id);

            if ($pagamento->estado !== EstadoPagamento::PENDENTE->value) {
                throw new EncomendaException(
                    'O pagamento já foi processado.',
                    409,
                    ['pagamento' => ['O pagamento já foi processado.']],
                );
            }

            $this->garantirEncomendaAguardandoPagamento($encomenda);

            $this->pagamentoRepository->confirmar(
                $pagamento,
                $dados['id_transacao'] ?? null,
                $dados['dados_resposta_operador'] ?? null,
            );

            $this->encomendaRepository->alterarEstado($encomenda, EstadoEncomenda::PAGA);

            $this->entregaService->criarParaEncomenda($encomenda);

            return $pagamento->fresh();
        });
    }

    /**
     * Garante que a encomenda ainda aguarda pagamento, senão lança exceção 409.
     *
     * @throws EncomendaException
     */
    private function garantirEncomendaAguardandoPagamento(Encomenda $encomenda): void
    {
        if ($encomenda->estado !== EstadoEncomenda::AGUARDANDO_PAGAMENTO->value) {
            throw new EncomendaException(
                'A encomenda não aguarda pagamento.',
                409,
                ['estado' => ['A encomenda não aguarda pagamento.']],
            );
        }
    }
}
