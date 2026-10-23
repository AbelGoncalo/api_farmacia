<?php

namespace App\Http\Controllers\Api\V1\Prescricao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescricao\AprovarPrescricaoRequest;
use App\Http\Requests\Prescricao\BuscarPrescricaoRequest;
use App\Http\Requests\Prescricao\ListarRevisaoPrescricoesRequest;
use App\Http\Requests\Prescricao\RejeitarPrescricaoRequest;
use App\Http\Resources\PrescricaoRevisaoResource;
use App\Models\User;
use App\Services\Prescricao\RevisaoPrescricaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoints de revisão de prescrições, reservados a profissionais
 * autorizados (gate `rever-prescricoes`).
 *
 * A revisão é sempre humana: estes endpoints preparam e registam a decisão
 * de um profissional, mas nunca aprovam automaticamente nem autorizam vendas.
 */
class PrescricaoRevisaoController extends Controller
{
    public function __construct(
        private readonly RevisaoPrescricaoService $revisaoService,
    ) {}

    /**
     * Lista a fila de revisão de prescrições.
     */
    public function index(ListarRevisaoPrescricoesRequest $request): JsonResponse
    {
        $resultado = $this->revisaoService->listarFila(
            $request->safe()->only(['estado']),
            (int) ($request->validated('por_pagina') ?? 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Fila de revisão carregada com sucesso.',
            'data' => PrescricaoRevisaoResource::collection($resultado->getCollection()),
            'meta' => [
                'pagina' => $resultado->currentPage(),
                'por_pagina' => $resultado->perPage(),
                'total' => $resultado->total(),
                'ultima_pagina' => $resultado->lastPage(),
            ],
        ]);
    }

    /**
     * Detalha uma prescrição para revisão, com o resultado bruto da IA.
     */
    public function show(BuscarPrescricaoRequest $request): JsonResponse
    {
        $prescricao = $this->revisaoService->buscarParaRevisao(
            $request->validated('prescricao'),
        );

        if ($prescricao === null) {
            return response()->json([
                'success' => false,
                'message' => 'Prescrição não encontrada.',
                'errors' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prescrição carregada com sucesso.',
            'data' => new PrescricaoRevisaoResource($prescricao),
        ]);
    }

    /**
     * Devolve o documento da prescrição para download pelo revisor.
     */
    public function documento(BuscarPrescricaoRequest $request): StreamedResponse|JsonResponse
    {
        $caminho = $this->revisaoService->caminhoDocumento(
            $request->validated('prescricao'),
        );

        if ($caminho === null) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não encontrado.',
                'errors' => [],
            ], 404);
        }

        return Storage::disk('local')->download($caminho);
    }

    /**
     * Aprova uma prescrição pendente de revisão.
     */
    public function aprovar(AprovarPrescricaoRequest $request): JsonResponse
    {
        $prescricao = $this->revisaoService->aprovar(
            $request->validated('prescricao'),
            $this->utilizador($request),
            $request->validated('notas_profissional'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Prescrição aprovada com sucesso.',
            'data' => new PrescricaoRevisaoResource($prescricao),
        ]);
    }

    /**
     * Rejeita uma prescrição pendente de revisão.
     */
    public function rejeitar(RejeitarPrescricaoRequest $request): JsonResponse
    {
        $prescricao = $this->revisaoService->rejeitar(
            $request->validated('prescricao'),
            $this->utilizador($request),
            $request->validated('motivo'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Prescrição rejeitada com sucesso.',
            'data' => new PrescricaoRevisaoResource($prescricao),
        ]);
    }

    /**
     * Devolve o utilizador autenticado associado ao pedido.
     */
    private function utilizador(Request $request): User
    {
        /** @var User $utilizador */
        $utilizador = $request->user();

        return $utilizador;
    }
}
