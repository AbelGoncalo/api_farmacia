<?php

namespace App\Services\Encomenda;

use App\Enums\Encomenda\EstadoEncomenda;
use App\Enums\Encomenda\EstadoEntrega;
use App\Enums\Encomenda\TipoEntrega;
use App\Exceptions\EncomendaException;
use App\Models\Encomenda;
use App\Models\Entrega;
use App\Models\Morada;
use App\Repositories\Encomenda\EncomendaRepository;
use App\Repositories\Encomenda\EntregaRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntregaService
{
    public function __construct(
        private readonly EntregaRepository $entregaRepository,
        private readonly EncomendaRepository $encomendaRepository,
    ) {}

    /**
     * Cria a entrega de uma encomenda recém-paga, consoante o tipo de entrega.
     *
     * Para entregas ao domicílio, guarda um instantâneo da morada de destino
     * (histórico imutável), de forma a isolar a entrega de alterações futuras
     * na morada do utilizador.
     */
    public function criarParaEncomenda(Encomenda $encomenda): Entrega
    {
        return $this->entregaRepository->criarEntrega([
            'id' => (string) Str::uuid(),
            'encomenda_id' => $encomenda->id,
            'tipo' => $encomenda->tipo_entrega,
            'estado' => EstadoEntrega::PENDENTE->value,
            'morada_historico' => $this->instantaneoMorada($encomenda),
        ]);
    }

    /**
     * Lista as entregas, opcionalmente filtradas por estado, de forma paginada.
     */
    public function listar(?string $estado, int $porPagina): LengthAwarePaginator
    {
        return $this->entregaRepository->listar(
            $estado !== null ? EstadoEntrega::from($estado) : null,
            $porPagina,
        );
    }

    /**
     * Inicia o transporte de uma entrega ao domicílio (PENDENTE → EM_TRANSPORTE).
     *
     * Dentro de uma transação: trava a entrega e a encomenda, valida que a entrega
     * está pendente, que é ao domicílio e que a encomenda foi paga, e move a
     * encomenda para ENVIADA.
     *
     * @return ?Entrega a entrega atualizada, ou null se não existir.
     *
     * @throws EncomendaException quando a entrega ou a encomenda não estão num estado válido.
     */
    public function iniciarTransporte(string $entregaId): ?Entrega
    {
        return DB::transaction(function () use ($entregaId): ?Entrega {
            $entrega = $this->entregaRepository->travar($entregaId);

            if ($entrega === null) {
                return null;
            }

            $this->garantirTipo($entrega, TipoEntrega::ENTREGA_DOMICILIO, 'iniciar o transporte');
            $this->garantirEstado($entrega, EstadoEntrega::PENDENTE, 'iniciar o transporte');

            $encomenda = $this->encomendaRepository->travar($entrega->encomenda_id);

            $this->garantirEncomendaEstado($encomenda, EstadoEncomenda::PAGA, 'iniciar o transporte');

            $this->entregaRepository->alterarEstado($entrega, EstadoEntrega::EM_TRANSPORTE);
            $this->encomendaRepository->alterarEstado($encomenda, EstadoEncomenda::ENVIADA);

            return $entrega->fresh();
        });
    }

    /**
     * Confirma o levantamento de uma encomenda na loja (PENDENTE → ENTREGUE).
     *
     * Dentro de uma transação: trava a entrega e a encomenda, valida que a entrega
     * é de recolha na loja e está pendente, e conclui ambas as operações.
     *
     * @return ?Entrega a entrega atualizada, ou null se não existir.
     *
     * @throws EncomendaException quando a entrega ou a encomenda não estão num estado válido.
     */
    public function confirmarLevantamento(string $entregaId): ?Entrega
    {
        return DB::transaction(function () use ($entregaId): ?Entrega {
            $entrega = $this->entregaRepository->travar($entregaId);

            if ($entrega === null) {
                return null;
            }

            $this->garantirTipo($entrega, TipoEntrega::RECOLHA_LOJA, 'confirmar o levantamento');
            $this->garantirEstado($entrega, EstadoEntrega::PENDENTE, 'confirmar o levantamento');

            $encomenda = $this->encomendaRepository->travar($entrega->encomenda_id);

            $this->garantirEncomendaEstado($encomenda, EstadoEncomenda::PAGA, 'confirmar o levantamento');

            $this->entregaRepository->registarLevantamento($entrega);
            $this->encomendaRepository->alterarEstado($encomenda, EstadoEncomenda::ENTREGUE);

            return $entrega->fresh();
        });
    }

    /**
     * Confirma a entrega de uma encomenda ao domicílio (EM_TRANSPORTE → ENTREGUE).
     *
     * Dentro de uma transação: trava a entrega e a encomenda, valida que a entrega
     * é ao domicílio, já saiu para transporte e que a encomenda está ENVIADA,
     * e conclui ambas as operações.
     *
     * @return ?Entrega a entrega atualizada, ou null se não existir.
     *
     * @throws EncomendaException quando a entrega ou a encomenda não estão num estado válido.
     */
    public function confirmarEntrega(string $entregaId): ?Entrega
    {
        return DB::transaction(function () use ($entregaId): ?Entrega {
            $entrega = $this->entregaRepository->travar($entregaId);

            if ($entrega === null) {
                return null;
            }

            $this->garantirTipo($entrega, TipoEntrega::ENTREGA_DOMICILIO, 'confirmar a entrega');
            $this->garantirEstado($entrega, EstadoEntrega::EM_TRANSPORTE, 'confirmar a entrega');

            $encomenda = $this->encomendaRepository->travar($entrega->encomenda_id);

            $this->garantirEncomendaEstado($encomenda, EstadoEncomenda::ENVIADA, 'confirmar a entrega');

            $this->entregaRepository->registarEntrega($entrega);
            $this->encomendaRepository->alterarEstado($encomenda, EstadoEncomenda::ENTREGUE);

            return $entrega->fresh();
        });
    }

    /**
     * Constrói o instantâneo imutável da morada de destino de uma encomenda.
     *
     * Recolhas na loja não têm morada; encomendas ao domicílio sem morada
     * associada devolvem null (não deveria acontecer, dada a validação).
     *
     * @return ?array<string, ?string>
     */
    private function instantaneoMorada(Encomenda $encomenda): ?array
    {
        if ($encomenda->tipo_entrega !== TipoEntrega::ENTREGA_DOMICILIO->value) {
            return null;
        }

        /** @var ?Morada $morada */
        $morada = $encomenda->morada;

        if ($morada === null) {
            return null;
        }

        return [
            'nome_destinatario' => $morada->nome_destinatario,
            'telefone' => $morada->telefone,
            'provincia' => $morada->provincia,
            'municipio' => $morada->municipio,
            'bairro' => $morada->bairro,
            'rua' => $morada->rua,
            'numero_porta' => $morada->numero_porta,
            'referencia' => $morada->referencia,
        ];
    }

    /**
     * Garante que a entrega é do tipo indicado, senão lança exceção 409.
     *
     * @throws EncomendaException
     */
    private function garantirTipo(Entrega $entrega, TipoEntrega $tipo, string $acao): void
    {
        if ($entrega->tipo !== $tipo->value) {
            $descricaoTipo = $tipo === TipoEntrega::RECOLHA_LOJA ? 'recolha na loja' : 'entrega ao domicílio';

            throw new EncomendaException(
                sprintf('Só é possível %s em encomendas de %s.', $acao, $descricaoTipo),
                409,
                ['tipo' => [sprintf('Só é possível %s em encomendas de %s.', $acao, $descricaoTipo)]],
            );
        }
    }

    /**
     * Garante que a entrega está no estado indicado, senão lança exceção 409.
     *
     * @throws EncomendaException
     */
    private function garantirEstado(Entrega $entrega, EstadoEntrega $estado, string $acao): void
    {
        if ($entrega->estado !== $estado->value) {
            throw new EncomendaException(
                sprintf('A entrega não permite %s no estado atual.', $acao),
                409,
                ['estado' => [sprintf('A entrega não permite %s no estado atual.', $acao)]],
            );
        }
    }

    /**
     * Garante que a encomenda associada está no estado indicado, senão lança exceção 409.
     *
     * @throws EncomendaException
     */
    private function garantirEncomendaEstado(Encomenda $encomenda, EstadoEncomenda $estado, string $acao): void
    {
        if ($encomenda->estado !== $estado->value) {
            throw new EncomendaException(
                sprintf('A encomenda não permite %s no estado atual.', $acao),
                409,
                ['estado' => [sprintf('A encomenda não permite %s no estado atual.', $acao)]],
            );
        }
    }
}
