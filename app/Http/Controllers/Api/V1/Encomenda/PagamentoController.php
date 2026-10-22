<?php

namespace App\Http\Controllers\Api\V1\Encomenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encomenda\ConfirmarPagamentoRequest;
use App\Http\Requests\Encomenda\RegistarPagamentoRequest;
use App\Http\Resources\PagamentoResource;
use App\Models\User;
use App\Services\Encomenda\PagamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagamentoController extends Controller
{
    public function __construct(
        private readonly PagamentoService $pagamentoService,
    ) {}

    /**
     * Regista um pagamento pendente para uma encomenda do utilizador autenticado.
     */
    public function registar(RegistarPagamentoRequest $request): JsonResponse
    {
        $pagamento = $this->pagamentoService->registar(
            $this->utilizador($request),
            $request->validated('encomenda'),
            $request->validated(),
        );

        if ($pagamento === null) {
            return $this->naoEncontrado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pagamento registado com sucesso.',
            'data' => new PagamentoResource($pagamento),
        ], 201);
    }

    /**
     * Confirma um pagamento pendente e move a encomenda para PAGA.
     *
     * Acesso restrito ao gate `processar-encomendas` (via middleware da rota).
     */
    public function confirmar(ConfirmarPagamentoRequest $request): JsonResponse
    {
        $pagamento = $this->pagamentoService->confirmar(
            $request->validated('pagamento'),
            $request->validated(),
        );

        if ($pagamento === null) {
            return $this->naoEncontrado();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pagamento confirmado com sucesso.',
            'data' => new PagamentoResource($pagamento),
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

    /**
     * Resposta 404 para registos inexistentes ou pertencentes a outros utilizadores.
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
