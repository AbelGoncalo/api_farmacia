<?php

namespace App\Repositories\Encomenda;

use App\Models\Pagamento;

class PagamentoRepository
{
    /**
     * Cria o registo de um pagamento pendente para uma encomenda.
     *
     * @param  array{id: string, encomenda_id: string, metodo: string, estado: string, valor: float, referencia?: ?string}  $dados
     */
    public function criarPagamento(array $dados): Pagamento
    {
        $pagamento = new Pagamento;
        $pagamento->id = $dados['id'];
        $pagamento->encomenda_id = $dados['encomenda_id'];
        $pagamento->metodo = $dados['metodo'];
        $pagamento->estado = $dados['estado'];
        $pagamento->valor = $dados['valor'];
        $pagamento->referencia = $dados['referencia'] ?? null;
        $pagamento->save();

        return $pagamento;
    }

    /**
     * Trava a linha do pagamento indicado (SELECT ... FOR UPDATE) ou null caso não exista.
     */
    public function travarPagamento(string $id): ?Pagamento
    {
        return Pagamento::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Procura um pagamento pendente da encomenda indicada, ou null caso não exista.
     */
    public function buscarPendenteDaEncomenda(string $encomendaId): ?Pagamento
    {
        return Pagamento::query()
            ->where('encomenda_id', $encomendaId)
            ->where('estado', 'PENDENTE')
            ->first();
    }

    /**
     * Confirma um pagamento, registando o identificador de transação,
     * a resposta do operador e o momento do pagamento.
     */
    public function confirmar(Pagamento $pagamento, ?string $idTransacao, ?array $dadosRespostaOperador): void
    {
        $pagamento->estado = 'CONFIRMADO';
        $pagamento->id_transacao = $idTransacao;
        $pagamento->dados_resposta_operador = $dadosRespostaOperador;
        $pagamento->pago_em = now();
        $pagamento->save();
    }

    /**
     * Marca como FALHADO todos os pagamentos pendentes de uma encomenda.
     *
     * Usado no cancelamento: um pagamento nunca cobrado não pode ficar
     * pendente contra uma encomenda que deixou de existir em termos de
     * negócio, sob risco de ser confirmado mais tarde.
     */
    public function falharPendentesDaEncomenda(string $encomendaId): void
    {
        Pagamento::query()
            ->where('encomenda_id', $encomendaId)
            ->where('estado', 'PENDENTE')
            ->update(['estado' => 'FALHADO']);
    }
}
