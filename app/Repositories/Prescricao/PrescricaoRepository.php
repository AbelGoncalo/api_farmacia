<?php

namespace App\Repositories\Prescricao;

use App\Enums\Prescricao\EstadoPrescricao;
use App\Integracoes\IA\ItemExtraido;
use App\Integracoes\IA\ResultadoExtracao;
use App\Models\Prescricao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PrescricaoRepository
{
    /**
     * Cria o registo de uma prescrição a partir dos dados já validados.
     *
     * @param  array{id: string, utilizador_id: int, caminho_ficheiro: string, tipo_ficheiro: string, estado: string, data_emissao?: ?string, validade_ate?: ?string}  $dados
     */
    public function criarPrescricao(array $dados): Prescricao
    {
        $prescricao = new Prescricao;
        $prescricao->id = $dados['id'];
        $prescricao->utilizador_id = $dados['utilizador_id'];
        $prescricao->caminho_ficheiro = $dados['caminho_ficheiro'];
        $prescricao->tipo_ficheiro = $dados['tipo_ficheiro'];
        $prescricao->estado = $dados['estado'];
        $prescricao->data_emissao = $dados['data_emissao'] ?? null;
        $prescricao->validade_ate = $dados['validade_ate'] ?? null;
        $prescricao->save();

        return $prescricao;
    }

    /**
     * Procura uma prescrição pelo identificador, com os seus itens e produto,
     * ou null caso não exista.
     */
    public function buscar(string $id): ?Prescricao
    {
        return Prescricao::query()
            ->with(['itens.produto:id,codigo,nome', 'revisor:id,nome'])
            ->where('id', $id)
            ->first();
    }

    /**
     * Procura uma prescrição pertencente ao utilizador, ou null caso não exista.
     */
    public function buscarDoUtilizador(string $id, int $utilizadorId): ?Prescricao
    {
        return Prescricao::query()
            ->with('itens.produto:id,codigo,nome')
            ->where('id', $id)
            ->where('utilizador_id', $utilizadorId)
            ->first();
    }

    /**
     * Devolve apenas o caminho do documento de uma prescrição do utilizador,
     * ou null caso a prescrição não pertença ao utilizador.
     */
    public function caminhoDoUtilizador(string $id, int $utilizadorId): ?string
    {
        return Prescricao::query()
            ->where('id', $id)
            ->where('utilizador_id', $utilizadorId)
            ->value('caminho_ficheiro');
    }

    /**
     * Trava a linha da prescrição indicada (SELECT ... FOR UPDATE) ou null caso não exista.
     */
    public function travar(string $id): ?Prescricao
    {
        return Prescricao::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Lista as prescrições do utilizador, de forma paginada.
     */
    public function listarPorUtilizador(int $utilizadorId, int $porPagina): LengthAwarePaginator
    {
        return Prescricao::query()
            ->with('itens.produto:id,codigo,nome')
            ->where('utilizador_id', $utilizadorId)
            ->orderByDesc('criado_em')
            ->paginate($porPagina);
    }

    /**
     * Altera o estado de uma prescrição.
     */
    public function alterarEstado(Prescricao $prescricao, EstadoPrescricao $estado): void
    {
        $prescricao->estado = $estado->value;
        $prescricao->save();
    }

    /**
     * Guarda o resultado da extração da IA (incluindo a confiança de
     * extração, que nunca se mistura com a de correspondência dos itens).
     */
    public function guardarResultadoIa(Prescricao $prescricao, ResultadoExtracao $resultado): void
    {
        $prescricao->resultado_ia = [
            'provedor' => $resultado->provedor,
            'confianca' => $resultado->confianca,
            'extraido_em' => now()->toIso8601String(),
            'itens' => array_map(fn (ItemExtraido $item) => [
                'texto_original' => $item->textoOriginal,
                'nome' => $item->nome,
                'dosagem' => $item->dosagem,
                'apresentacao' => $item->apresentacao,
                'quantidade' => $item->quantidade,
            ], $resultado->itens),
        ];
        $prescricao->save();
    }

    /**
     * Regista uma falha de análise: estado ERRO_ANALISE e mensagem de erro
     * (nunca exposta ao cliente). Usa atualização dirigida para não persistir
     * eventuais alterações em memória que tenham ficado por uma transação
     * revertida.
     */
    public function registarErroAnalise(Prescricao $prescricao, string $mensagemErro): void
    {
        Prescricao::query()
            ->where('id', $prescricao->id)
            ->update([
                'estado' => EstadoPrescricao::ERRO_ANALISE->value,
                'mensagem_erro' => $mensagemErro,
            ]);
    }

    /**
     * Lista prescrições na fila de revisão, de forma paginada.
     *
     * Ao contrário das vistas do cliente, esta fila mostra prescrições de
     * qualquer utilizador (é uma fila de trabalho do profissional).
     *
     * @param  array{estado?: string}  $filtros
     */
    public function listarParaRevisao(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return Prescricao::query()
            ->with('itens.produto:id,codigo,nome')
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->orderByDesc('criado_em')
            ->paginate($porPagina);
    }

    /**
     * Trava uma prescrição e valida que está num estado de origem aceitável
     * para a transição pretendida (SELECT ... FOR UPDATE).
     *
     * Devolve a prescrição quando a transição é legítima, ou null quando não
     * existe, não está no estado de origem, ou já foi revista. O bloqueio
     * impede revisões concorrentes da mesma prescrição.
     *
     * @param  list<EstadoPrescricao>  $estadosOrigem
     */
    public function travarParaRevisao(string $id, array $estadosOrigem): ?Prescricao
    {
        $prescricao = Prescricao::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        if ($prescricao === null) {
            return null;
        }

        if (! in_array($prescricao->estado, array_map(fn ($estado) => $estado->value, $estadosOrigem), true)) {
            return null;
        }

        return $prescricao;
    }

    /**
     * Regista a decisão de revisão: estado final, revisor, data da revisão e
     * notas do profissional.
     */
    public function registarRevisao(
        Prescricao $prescricao,
        EstadoPrescricao $estado,
        int $revisorId,
        ?string $notas,
    ): void {
        $prescricao->estado = $estado->value;
        $prescricao->revisto_por = $revisorId;
        $prescricao->revisto_em = now();
        $prescricao->notas_profissional = $notas;
        $prescricao->save();
    }
}
