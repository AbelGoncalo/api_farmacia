<?php

namespace App\Http\Controllers\Api\V1\Encomenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encomenda\ConfirmarEntregaRequest;
use App\Http\Requests\Encomenda\ConfirmarLevantamentoRequest;
use App\Http\Requests\Encomenda\IniciarTransporteRequest;
use App\Http\Requests\Encomenda\ListarEntregasRequest;
use App\Http\Resources\EntregaResource;
use App\Services\Encomenda\EntregaService;
use Illuminate\Http\JsonResponse;

class EntregaController extends Controller
{
    public function __construct(
        private readonly EntregaService $entregaService,
    ) {}

    /**
     * Lista as entregas, opcionalmente filtradas por estado.
     *
     * Acesso restrito ao gate `processar-encomendas` (via middleware da rota).
     */
    public function index(ListarEntregasRequest $request): JsonResponse
    {
        $resultado = $this->entregaService->listar(
            $request->validated('estado'),
            (int) ($request->validated('por_pagina') ?? 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Entregas carregadas com sucesso.',
            'data' => EntregaResource::collection($resultado->getCollection()),
            'meta' => [
                'pagina' => $resultado->currentPage(),
                'por_pagina' => $resultado->perPage(),
                'total' => $resultado->total(),
                'ultima_pagina' => $resultado->lastPage(),
            ],
        ]);
    }

    /**
     * Inicia o transporte de uma entrega ao domicílio (encomenda passa a ENVIADA).
     *
     * Acesso restrito ao gate `processar-encomendas` (via middleware da rota).
     */
    public function iniciarTransporte(IniciarTransporteRequest $request): JsonResponse
    {
        $entrega = $this->entregaService->iniciarTransporte($request->validated('entrega'));

        if ($entrega === null) {
            return $this->naoEncontrado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Transporte iniciado com sucesso.',
            'data' => new EntregaResource($entrega),
        ]);
    }

    /**
     * Confirma o levantamento de uma encomenda na loja (encomenda passa a ENTREGUE).
     *
     * Acesso restrito ao gate `processar-encomendas` (via middleware da rota).
     */
    public function confirmarLevantamento(ConfirmarLevantamentoRequest $request): JsonResponse
    {
        $entrega = $this->entregaService->confirmarLevantamento($request->validated('entrega'));

        if ($entrega === null) {
            return $this->naoEncontrado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Levantamento confirmado com sucesso.',
            'data' => new EntregaResource($entrega),
        ]);
    }

    /**
     * Confirma a entrega de uma encomenda ao domicílio (encomenda passa a ENTREGUE).
     *
     * Acesso restrito ao gate `processar-encomendas` (via middleware da rota).
     */
    public function confirmarEntrega(ConfirmarEntregaRequest $request): JsonResponse
    {
        $entrega = $this->entregaService->confirmarEntrega($request->validated('entrega'));

        if ($entrega === null) {
            return $this->naoEncontrado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Entrega confirmada com sucesso.',
            'data' => new EntregaResource($entrega),
        ]);
    }

    /**
     * Resposta 404 para registos inexistentes.
     */
    private function naoEncontrado(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Registo não encontrado.',
            'errors' => [],
        ], 404);
    }
}
