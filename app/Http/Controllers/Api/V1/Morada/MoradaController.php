<?php

namespace App\Http\Controllers\Api\V1\Morada;

use App\Http\Controllers\Controller;
use App\Http\Requests\Morada\BuscarMoradaRequest;
use App\Http\Requests\Morada\StoreMoradaRequest;
use App\Http\Requests\Morada\UpdateMoradaRequest;
use App\Http\Resources\MoradaResource;
use App\Models\Morada;
use App\Models\User;
use App\Services\Morada\MoradaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoradaController extends Controller
{
    public function __construct(
        private readonly MoradaService $moradaService,
    ) {}

    /**
     * Lista as moradas ativas do utilizador autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $moradas = $this->moradaService->listar($this->utilizador($request));

        return response()->json([
            'success' => true,
            'message' => 'Moradas carregadas com sucesso.',
            'data' => MoradaResource::collection($moradas),
        ]);
    }

    /**
     * Cria uma morada para o utilizador autenticado.
     */
    public function store(StoreMoradaRequest $request): JsonResponse
    {
        $morada = $this->moradaService->criar(
            $this->utilizador($request),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Morada criada com sucesso.',
            'data' => new MoradaResource($morada),
        ], 201);
    }

    /**
     * Detalha uma morada do próprio utilizador autenticado.
     */
    public function show(BuscarMoradaRequest $request): JsonResponse
    {
        $morada = $this->moradaService->buscar(
            $this->utilizador($request),
            (string) $request->validated('morada'),
        );

        if ($morada === null) {
            return $this->moradaNaoEncontrada();
        }

        return response()->json([
            'success' => true,
            'message' => 'Morada carregada com sucesso.',
            'data' => new MoradaResource($morada),
        ]);
    }

    /**
     * Atualiza uma morada do próprio utilizador autenticado.
     */
    public function update(UpdateMoradaRequest $request): JsonResponse
    {
        $morada = $this->moradaService->atualizar(
            $this->utilizador($request),
            (string) $request->validated('morada'),
            $request->validated(),
        );

        if ($morada === null) {
            return $this->moradaNaoEncontrada();
        }

        return response()->json([
            'success' => true,
            'message' => 'Morada atualizada com sucesso.',
            'data' => new MoradaResource($morada),
        ]);
    }

    /**
     * Desativa uma morada do próprio utilizador autenticado.
     *
     * A linha não é apagada: preserva o vínculo com encomendas antigas.
     */
    public function destroy(BuscarMoradaRequest $request): JsonResponse
    {
        $morada = $this->moradaService->desativar(
            $this->utilizador($request),
            (string) $request->validated('morada'),
        );

        if ($morada === null) {
            return $this->moradaNaoEncontrada();
        }

        return response()->json([
            'success' => true,
            'message' => 'Morada desativada com sucesso.',
            'data' => new MoradaResource($morada),
        ]);
    }

    private function utilizador(Request $request): User
    {
        /** @var User $utilizador */
        $utilizador = $request->user();

        return $utilizador;
    }

    private function moradaNaoEncontrada(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Morada não encontrada.',
            'errors' => [],
        ], 404);
    }
}
