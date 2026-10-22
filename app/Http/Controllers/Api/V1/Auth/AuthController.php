<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function registar(Request $request): JsonResponse
    {
        $dados = $this->normalizarCampos($request->all(), ['nome', 'email', 'telefone', 'nif']);
        $dados = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')],
            'telefone' => ['nullable', 'string', 'max:30'],
            'nif' => ['nullable', 'string', 'max:30'],
            'palavra_passe' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode exceder 150 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email indicado não é válido.',
            'email.unique' => 'Já existe uma conta com este email.',
            'telefone.max' => 'O telefone não pode exceder 30 caracteres.',
            'nif.max' => 'O NIF não pode exceder 30 caracteres.',
            'palavra_passe.required' => 'A palavra-passe é obrigatória.',
            'palavra_passe.confirmed' => 'A confirmação da palavra-passe não coincide.',
        ])->validate();
        $cliente = $this->authService->criarConta($dados);

        return response()->json([
            'success' => true,
            'message' => 'Conta de cliente criada com sucesso.',
            'data' => new UserResource($cliente),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $this->normalizarCampos($request->all(), ['email']);
        $dados = Validator::make($dados, [
            'email' => ['required', 'string', 'email', 'max:180'],
            'palavra_passe' => ['required', 'string'],
        ])->validate();
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

    public function atualizar(Request $request): JsonResponse
    {
        /** @var User $cliente */
        $cliente = $request->user();
        $dados = $this->normalizarCampos($request->all(), ['nome', 'email', 'telefone', 'nif']);
        $dados = Validator::make($dados, [
            'nome' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($cliente->getAuthIdentifier()),
            ],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nif' => ['sometimes', 'nullable', 'string', 'max:30'],
        ], [
            'nome.required' => 'O nome não pode ficar vazio.',
            'email.required' => 'O email não pode ficar vazio.',
            'email.email' => 'O email indicado não é válido.',
            'email.unique' => 'Já existe uma conta com este email.',
        ])->validate();

        if ($dados === []) {
            return response()->json([
                'success' => false,
                'message' => 'Indica pelo menos um campo para atualizar.',
                'errors' => ['dados' => ['Indica pelo menos um campo para atualizar.']],
            ], 422);
        }

        $clienteAtualizado = $this->authService->atualizarConta($cliente, $dados);

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

    /** @param list<string> $campos @param array<string, mixed> $dados @return array<string, mixed> */
    private function normalizarCampos(array $dados, array $campos): array
    {
        foreach ($campos as $campo) {
            if (isset($dados[$campo]) && is_string($dados[$campo])) {
                $dados[$campo] = trim($dados[$campo]);
            }
        }

        if (isset($dados['email'])) {
            $dados['email'] = mb_strtolower($dados['email']);
        }

        return $dados;
    }
}
