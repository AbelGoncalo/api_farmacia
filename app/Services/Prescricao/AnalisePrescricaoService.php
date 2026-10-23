<?php

namespace App\Services\Prescricao;

use App\Enums\Prescricao\EstadoPrescricao;
use App\Exceptions\PrescricaoException;
use App\Integracoes\IA\ExtratorPrescricao;
use App\Integracoes\IA\ItemExtraido;
use App\Integracoes\IA\ResultadoExtracao;
use App\Models\Prescricao;
use App\Repositories\Prescricao\ItemPrescricaoRepository;
use App\Repositories\Prescricao\PrescricaoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orquestra a análise automática de uma prescrição submetida.
 *
 * Lê o documento do disco privado, delega a extração ao provedor de IA e
 * corresponde cada item com o catálogo. A extração e a correspondência são
 * apenas sugestões: a análise termina sempre em AGUARDA_REVISAO para decisão
 * manual de um profissional autorizado, nunca aprova nem autoriza vendas.
 *
 * Qualquer falha fica registada em ERRO_ANALISE com mensagem_erro (nunca
 * exposta ao cliente) — a submissão nunca falha por causa da análise.
 */
class AnalisePrescricaoService
{
    public function __construct(
        private readonly PrescricaoRepository $prescricaoRepository,
        private readonly ItemPrescricaoRepository $itemPrescricaoRepository,
        private readonly ExtratorPrescricao $extrator,
        private readonly CorrespondenciaService $correspondenciaService,
    ) {}

    /**
     * Executa a análise de uma prescrição: extração dos itens, correspondência
     * com o catálogo e transição de estado. Idempotente: só processa
     * prescrições em estado PENDENTE.
     */
    public function analisar(string $prescricaoId): void
    {
        $prescricao = $this->prescricaoRepository->travar($prescricaoId);

        if ($prescricao === null || $prescricao->estado !== EstadoPrescricao::PENDENTE->value) {
            return;
        }

        $this->prescricaoRepository->alterarEstado($prescricao, EstadoPrescricao::EM_ANALISE);

        try {
            $resultado = $this->extrator->extrair(
                $this->lerDocumento($prescricao),
                $prescricao->tipo_ficheiro,
            );

            $this->guardarResultado($prescricao, $resultado);
        } catch (Throwable $erro) {
            $this->registarFalha($prescricao, $erro);
        }
    }

    private function lerDocumento(Prescricao $prescricao): string
    {
        $conteudo = Storage::disk('local')->get($prescricao->caminho_ficheiro);

        if ($conteudo === null || $conteudo === '') {
            throw new PrescricaoException('O documento da prescrição não pôde ser lido.');
        }

        return $conteudo;
    }

    /**
     * Guarda os itens com as respetivas sugestões de correspondência e o
     * resultado da extração, dentro de uma transação.
     */
    private function guardarResultado(Prescricao $prescricao, ResultadoExtracao $resultado): void
    {
        DB::transaction(function () use ($prescricao, $resultado): void {
            foreach ($resultado->itens as $item) {
                $this->criarItem($prescricao, $item);
            }

            $this->prescricaoRepository->guardarResultadoIa($prescricao, $resultado);
            $this->prescricaoRepository->alterarEstado($prescricao, EstadoPrescricao::AGUARDA_REVISAO);
        });
    }

    private function criarItem(Prescricao $prescricao, ItemExtraido $item): void
    {
        $sugestao = $this->correspondenciaService->sugerir($item->nome);

        $this->itemPrescricaoRepository->criarItem([
            'id' => (string) Str::uuid(),
            'prescricao_id' => $prescricao->id,
            'texto_original' => $item->textoOriginal,
            'nome_medicamento' => $item->nome,
            'dosagem' => $item->dosagem,
            'apresentacao' => $item->apresentacao,
            'quantidade' => $item->quantidade,
            'produto_id' => $sugestao->produto?->id,
            'estado_correspondencia' => $sugestao->estado->value,
            'metodo_correspondencia' => $sugestao->metodo,
            'confianca_correspondencia' => $sugestao->confianca,
        ]);
    }

    private function registarFalha(Prescricao $prescricao, Throwable $erro): void
    {
        Log::warning('Análise de prescrição falhou.', [
            'prescricao_id' => $prescricao->id,
            'erro' => $erro->getMessage(),
        ]);

        $this->prescricaoRepository->registarErroAnalise($prescricao, $erro->getMessage());
    }
}
