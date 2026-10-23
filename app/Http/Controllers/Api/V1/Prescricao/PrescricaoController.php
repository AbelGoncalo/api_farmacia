<?php

namespace App\Http\Controllers\Api\V1\Prescricao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescricao\BuscarPrescricaoRequest;
use App\Http\Requests\Prescricao\ListarPrescricoesRequest;
use App\Http\Requests\Prescricao\SubmeterPrescricaoRequest;
use App\Http\Resources\PrescricaoResource;
use App\Models\User;
use App\Services\Prescricao\PrescricaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrescricaoController extends Controller
{
    public function __construct(
        private readonly PrescricaoService $prescricaoService,
    ) {}

    /**
     * Lista as prescrições do utilizador autenticado.
     */
    public function index(ListarPrescricoesRequest $request): JsonResponse
    {
        $resultado = $this->prescricaoService->listar(
            $this->utilizador($request),
            (int) ($request->validated('por_pagina') ?? 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Prescrições carregadas com sucesso.',
            'data' => PrescricaoResource::collection($resultado->getCollection()),
            'meta' => [
                'pagina' => $resultado->currentPage(),
                'por_pagina' => $resultado->perPage(),
                'total' => $resultado->total(),
                'ultima_pagina' => $resultado->lastPage(),
            ],
        ]);
    }

    /**
     * Submete uma nova prescrição com o documento enviado.
     */
    public function store(SubmeterPrescricaoRequest $request): JsonResponse
    {
        $prescricao = $this->prescricaoService->submeter(
            $this->utilizador($request),
            $request->file('ficheiro'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Prescrição submetida com sucesso.',
            'data' => new PrescricaoResource($prescricao),
        ], 201);
    }

    /**
     * Detalha uma prescrição do utilizador autenticado.
     */
    public function show(BuscarPrescricaoRequest $request): JsonResponse
    {
        $prescricao = $this->prescricaoService->buscar(
            $this->utilizador($request),
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
            'data' => new PrescricaoResource($prescricao),
        ]);
    }

    /**
     * Devolve o documento da prescrição para download.
     */
    public function documento(BuscarPrescricaoRequest $request): StreamedResponse|JsonResponse
    {
        $caminho = $this->prescricaoService->caminhoDocumento(
            $this->utilizador($request),
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
     * Devolve o utilizador autenticado associado ao pedido.
     */
    private function utilizador(Request $request): User
    {
        /** @var User $utilizador */
        $utilizador = $request->user();

        return $utilizador;
    }
}
