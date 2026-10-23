<?php

namespace App\Services\Prescricao;

use App\Enums\Prescricao\EstadoPrescricao;
use App\Exceptions\PrescricaoException;
use App\Models\Prescricao;
use App\Models\User;
use App\Repositories\Prescricao\PrescricaoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Revisão manual de prescrições por um profissional autorizado.
 *
 * A aprovação é sempre humana: a análise da IA apenas prepara a prescrição
 * para revisão (AGUARDA_REVISAO) e nunca aprova nem autoriza vendas. Cada
 * decisão trava a linha da prescrição para impedir revisões concorrentes e
 * regista quem decidiu, quando e com que notas.
 */
class RevisaoPrescricaoService
{
    /** Estados a partir dos quais é possível decidir (revisão pendente). */
    private const ESTADOS_REVISAVEIS = [
        EstadoPrescricao::AGUARDA_REVISAO,
        EstadoPrescricao::ERRO_ANALISE,
    ];

    public function __construct(
        private readonly PrescricaoRepository $prescricaoRepository,
    ) {}

    /**
     * Lista a fila de revisão, de forma paginada, opcionalmente filtrada.
     *
     * @param  array{estado?: string}  $filtros
     */
    public function listarFila(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return $this->prescricaoRepository->listarParaRevisao($filtros, $porPagina);
    }

    /**
     * Procura uma prescrição para revisão (independentemente do dono).
     */
    public function buscarParaRevisao(string $id): ?Prescricao
    {
        return $this->prescricaoRepository->buscar($id);
    }

    /**
     * Devolve o caminho do documento de qualquer prescrição para download
     * pelo revisor, quando o registo e o ficheiro existem.
     */
    public function caminhoDocumento(string $id): ?string
    {
        $caminho = $this->prescricaoRepository->buscar($id)?->caminho_ficheiro;

        if ($caminho === null || ! Storage::disk('local')->exists($caminho)) {
            return null;
        }

        return $caminho;
    }

    /**
     * Aprova uma prescrição pendente de revisão.
     *
     * @throws PrescricaoException quando a prescrição não existe ou não está
     *                             pendente de revisão.
     */
    public function aprovar(string $id, User $revisor, ?string $notas): Prescricao
    {
        return $this->decidir($id, $revisor, EstadoPrescricao::APROVADA, $notas);
    }

    /**
     * Rejeita uma prescrição pendente de revisão.
     *
     * @throws PrescricaoException quando a prescrição não existe ou não está
     *                             pendente de revisão.
     */
    public function rejeitar(string $id, User $revisor, string $motivo): Prescricao
    {
        return $this->decidir($id, $revisor, EstadoPrescricao::REJEITADA, $motivo);
    }

    /**
     * Aplica a decisão de revisão dentro de um bloqueio de linha.
     */
    private function decidir(
        string $id,
        User $revisor,
        EstadoPrescricao $estado,
        ?string $notas,
    ): Prescricao {
        $prescricao = $this->prescricaoRepository->travarParaRevisao($id, self::ESTADOS_REVISAVEIS);

        if ($prescricao === null) {
            throw new PrescricaoException(
                'A prescrição não está pendente de revisão.',
                409,
                [],
            );
        }

        $this->prescricaoRepository->registarRevisao($prescricao, $estado, $revisor->id, $notas);

        return $prescricao->fresh('itens.produto:id,codigo,nome');
    }
}
