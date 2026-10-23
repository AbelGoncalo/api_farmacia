<?php

namespace App\Repositories\Encomenda;

use App\Enums\Encomenda\EstadoEntrega;
use App\Models\Entrega;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EntregaRepository
{
    /**
     * Cria o registo de uma entrega a partir dos dados já calculados pelo serviço.
     *
     * @param  array{id: string, encomenda_id: string, tipo: string, estado: string, morada_historico?: ?array}  $dados
     */
    public function criarEntrega(array $dados): Entrega
    {
        $entrega = new Entrega;
        $entrega->id = $dados['id'];
        $entrega->encomenda_id = $dados['encomenda_id'];
        $entrega->tipo = $dados['tipo'];
        $entrega->estado = $dados['estado'];
        $entrega->morada_historico = $dados['morada_historico'] ?? null;
        $entrega->save();

        return $entrega;
    }

    /**
     * Trava a linha da entrega indicada (SELECT ... FOR UPDATE) ou null caso não exista.
     */
    public function travar(string $id): ?Entrega
    {
        return Entrega::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Procura a entrega associada a uma encomenda, ou null caso não exista.
     */
    public function buscarDaEncomenda(string $encomendaId): ?Entrega
    {
        return Entrega::query()
            ->where('encomenda_id', $encomendaId)
            ->first();
    }

    /**
     * Lista as entregas, opcionalmente filtradas por estado, de forma paginada.
     */
    public function listar(?EstadoEntrega $estado, int $porPagina): LengthAwarePaginator
    {
        return Entrega::query()
            ->with('encomenda:id,numero_encomenda,estado,tipo_entrega')
            ->when($estado !== null, fn ($query) => $query->where('estado', $estado->value))
            ->orderByDesc('criado_em')
            ->paginate($porPagina);
    }

    /**
     * Cancela a entrega pendente de uma encomenda, se existir e ainda
     * não tiver começado (cancelamento da encomenda associada).
     */
    public function cancelarPendenteDaEncomenda(string $encomendaId): void
    {
        Entrega::query()
            ->where('encomenda_id', $encomendaId)
            ->where('estado', EstadoEntrega::PENDENTE->value)
            ->update(['estado' => EstadoEntrega::CANCELADA->value]);
    }

    /**
     * Atualiza o estado da entrega.
     */
    public function alterarEstado(Entrega $entrega, EstadoEntrega $estado): void
    {
        $entrega->estado = $estado->value;
        $entrega->save();
    }

    /**
     * Regista o levantamento na loja, marcando a entrega como concluída.
     */
    public function registarLevantamento(Entrega $entrega): void
    {
        $entrega->estado = EstadoEntrega::ENTREGUE->value;
        $entrega->levantamento_confirmado_em = now();
        $entrega->entregue_em = now();
        $entrega->save();
    }

    /**
     * Regista a entrega ao domicílio, marcando a entrega como concluída.
     */
    public function registarEntrega(Entrega $entrega): void
    {
        $entrega->estado = EstadoEntrega::ENTREGUE->value;
        $entrega->entregue_em = now();
        $entrega->save();
    }
}
