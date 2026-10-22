<?php

namespace App\Http\Controllers\Api\V1\Encomenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encomenda\BuscarEncomendaRequest;
use App\Http\Requests\Encomenda\CriarEncomendaRequest;
use App\Http\Resources\EncomendaResource;
use App\Models\User;
use App\Services\Encomenda\EncomendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncomendaController extends Controller
{
    public function __construct(
        private readonly EncomendaService $encomendaService,
    ) {}

    /**
     * Lista as encomendas do utilizador autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $resultado = $this->encomendaService->listar(
            $this->utilizador($request),
            (int) $request->query('por_pagina', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Encomendas carregadas com sucesso.',
            'data' => EncomendaResource::collection($resultado->getCollection()),
            'meta' => [
                'pagina' => $resultado->currentPage(),
                'por_pagina' => $resultado->perPage(),
                'total' => $resultado->total(),
                'ultima_pagina' => $resultado->lastPage(),
            ],
        ]);
    }

    /**
     * Cria uma encomenda a partir do carrinho do utilizador autenticado.
     */
    public function store(CriarEncomendaRequest $request): JsonResponse
    {
        $encomenda = $this->encomendaService->criar(
            $this->utilizador($request),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Encomenda criada com sucesso.',
            'data' => new EncomendaResource($encomenda),
        ], 201);
    }

    /**
     * Detalha uma encomenda do utilizador autenticado.
     */
    public function show(BuscarEncomendaRequest $request): JsonResponse
    {
        $encomenda = $this->encomendaService->buscar(
            $this->utilizador($request),
            $request->validated('encomenda'),
        );

        if ($encomenda === null) {
            return response()->json([
                'success' => false,
                'message' => 'Encomenda não encontrada.',
                'errors' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Encomenda carregada com sucesso.',
            'data' => new EncomendaResource($encomenda),
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
