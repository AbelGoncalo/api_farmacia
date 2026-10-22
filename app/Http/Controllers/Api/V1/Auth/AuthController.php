<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AtualizarContaRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function registar(RegistarRequest $request): JsonResponse
    {
        $cliente = $this->authService->criarConta($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conta de cliente criada com sucesso.',
            'data' => new UserResource($cliente),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $resultado = $this->authService->login($dados['email'], $dados['palavra_passe']);

        if ($resultado === null) {
            return response()->json([
                'success' => false,
                'message' => 'As credenciais são inválidas.',
                'errors' => [],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticação realizada com sucesso.',
            'data' => [
                'token' => $resultado['token'],
                'tipo' => 'Bearer',
                'expira_em_segundos' => $resultado['expira_em_segundos'],
                'papel' => $resultado['papel'],
                'destino' => $resultado['destino'],
                'cliente' => new UserResource($resultado['cliente']),
            ],
        ]);
    }

    public function atualizar(AtualizarContaRequest $request): JsonResponse
    {
        /** @var User $cliente */
        $cliente = $request->user();

        $this->authorize('update', $cliente);

        $clienteAtualizado = $this->authService->atualizarConta($cliente, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conta atualizada com sucesso.',
            'data' => new UserResource($clienteAtualizado),
        ]);
    }

    public function sair(): JsonResponse
    {
        $this->authService->sair();

        return response()->json([
            'success' => true,
            'message' => 'Sessão terminada com sucesso.',
            'data' => null,
        ]);
    }
}
