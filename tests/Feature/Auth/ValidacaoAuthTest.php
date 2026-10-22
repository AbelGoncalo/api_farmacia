<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacaoAuthTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(): string
    {
        $utilizador = User::factory()->create([
            'papel' => 'CLIENTE',
            'estado' => 'ATIVO',
        ]);

        $token = $this->postJson('/api/v1/autenticacao/login', [
            'email' => $utilizador->email,
            'palavra_passe' => 'password',
        ])->json('data.token');

        $this->assertIsString($token);

        return $token;
    }

    public function test_registar_normaliza_email_e_nome(): void
    {
        $response = $this->postJson('/api/v1/autenticacao/registar', [
            'nome' => '  Maria Silva  ',
            'email' => '  MARIA@Example.COM ',
            'palavra_passe' => 'segredo123',
            'palavra_passe_confirmation' => 'segredo123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
        ]);
    }

    public function test_registar_valida_campos_obrigatorios(): void
    {
        $response = $this->postJson('/api/v1/autenticacao/registar', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors' => ['nome', 'email', 'palavra_passe']]);
    }

    public function test_registar_rejeita_email_duplicado(): void
    {
        $utilizador = User::factory()->create();

        $this->postJson('/api/v1/autenticacao/registar', [
            'nome' => 'Outro Cliente',
            'email' => $utilizador->email,
            'palavra_passe' => 'segredo123',
            'palavra_passe_confirmation' => 'segredo123',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'Já existe uma conta com este email.');
    }

    public function test_login_normaliza_email(): void
    {
        $utilizador = User::factory()->create([
            'papel' => 'CLIENTE',
            'estado' => 'ATIVO',
        ]);

        $response = $this->postJson('/api/v1/autenticacao/login', [
            'email' => '  '.mb_strtoupper($utilizador->email).' ',
            'palavra_passe' => 'password',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['token', 'papel', 'destino']]);
    }

    public function test_login_com_credenciais_invalidas_retorna_401(): void
    {
        User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);

        $this->postJson('/api/v1/autenticacao/login', [
            'email' => 'inexistente@example.com',
            'palavra_passe' => 'errada',
        ])->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_atualizar_sem_campos_retorna_422(): void
    {
        $response = $this->withToken($this->autenticar())
            ->patchJson('/api/v1/autenticacao/atualizar', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Indica pelo menos um campo para atualizar.');
    }

    public function test_atualizar_permite_definir_telefone_nulo(): void
    {
        $this->withToken($this->autenticar())
            ->patchJson('/api/v1/autenticacao/atualizar', ['telefone' => null])
            ->assertStatus(200);
    }

    public function test_atualizar_sem_autenticacao_retorna_401(): void
    {
        $this->patchJson('/api/v1/autenticacao/atualizar', ['nome' => 'X'])->assertStatus(401);
    }

    public function test_atualizar_rejeita_email_duplicado_de_outro_utilizador(): void
    {
        $token = $this->autenticar();
        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);

        $this->withToken($token)
            ->patchJson('/api/v1/autenticacao/atualizar', ['email' => $outro->email])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Já existe uma conta com este email.');
    }
}
