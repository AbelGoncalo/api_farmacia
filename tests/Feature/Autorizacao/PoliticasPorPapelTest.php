<?php

namespace Tests\Feature\Autorizacao;

use App\Enums\Auth\PapelUtilizador;
use App\Models\Carrinho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class PoliticasPorPapelTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): User
    {
        return User::factory()->create(['papel' => 'CLIENTE']);
    }

    private function sistema(): User
    {
        return User::factory()->create(['papel' => 'UTILIZADOR_SISTEMA']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['papel' => 'SUPER_ADMIN']);
    }

    private function carrinhoDe(User $utilizador): Carrinho
    {
        $carrinho = new Carrinho;
        $carrinho->id = (string) Str::uuid();
        $carrinho->utilizador_id = $utilizador->id;
        $carrinho->estado = Carrinho::ESTADO_ATIVO;
        $carrinho->save();

        return $carrinho;
    }

    public function test_papel_enum_e_helper_tem_papel(): void
    {
        $cliente = $this->cliente();

        $this->assertSame(PapelUtilizador::CLIENTE, $cliente->papelEnum());
        $this->assertTrue($cliente->temPapel(PapelUtilizador::CLIENTE));
        $this->assertFalse($cliente->temPapel(PapelUtilizador::SUPER_ADMIN));
        $this->assertTrue($cliente->temPapel(PapelUtilizador::CLIENTE, PapelUtilizador::SUPER_ADMIN));
    }

    public function test_papel_enum_nulo_para_valor_invalido(): void
    {
        $utilizador = User::factory()->create(['papel' => 'INVALIDO']);

        $this->assertNull($utilizador->papelEnum());
        $this->assertFalse($utilizador->temPapel(PapelUtilizador::CLIENTE));
    }

    public function test_user_policy_update_so_dono_ou_super_admin(): void
    {
        $cliente = $this->cliente();
        $outro = $this->cliente();
        $super = $this->superAdmin();

        $this->assertTrue(Gate::forUser($cliente)->allows('update', $cliente));
        $this->assertFalse(Gate::forUser($cliente)->allows('update', $outro));
        $this->assertTrue(Gate::forUser($super)->allows('update', $cliente));
    }

    public function test_user_policy_delete_so_super_admin(): void
    {
        $cliente = $this->cliente();
        $sistema = $this->sistema();
        $super = $this->superAdmin();

        $this->assertFalse(Gate::forUser($cliente)->allows('delete', $cliente));
        $this->assertFalse(Gate::forUser($sistema)->allows('delete', $cliente));
        $this->assertTrue(Gate::forUser($super)->allows('delete', $cliente));
    }

    public function test_user_policy_view_any_somente_equipa(): void
    {
        $cliente = $this->cliente();
        $sistema = $this->sistema();
        $super = $this->superAdmin();

        $this->assertFalse(Gate::forUser($cliente)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($sistema)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($super)->allows('viewAny', User::class));
    }

    public function test_carrinho_policy_dono_ou_super_admin(): void
    {
        $cliente = $this->cliente();
        $outro = $this->cliente();
        $super = $this->superAdmin();
        $carrinho = $this->carrinhoDe($cliente);

        $this->assertTrue(Gate::forUser($cliente)->allows('view', $carrinho));
        $this->assertTrue(Gate::forUser($cliente)->allows('update', $carrinho));
        $this->assertTrue(Gate::forUser($cliente)->allows('delete', $carrinho));

        $this->assertFalse(Gate::forUser($outro)->allows('view', $carrinho));
        $this->assertFalse(Gate::forUser($outro)->allows('delete', $carrinho));

        $this->assertTrue(Gate::forUser($super)->allows('view', $carrinho));
        $this->assertTrue(Gate::forUser($super)->allows('delete', $carrinho));
    }

    public function test_gates_de_papel(): void
    {
        $cliente = $this->cliente();
        $sistema = $this->sistema();
        $super = $this->superAdmin();

        $this->assertFalse(Gate::forUser($cliente)->allows('gerir-catalogo'));
        $this->assertTrue(Gate::forUser($sistema)->allows('gerir-catalogo'));
        $this->assertTrue(Gate::forUser($super)->allows('gerir-catalogo'));

        $this->assertFalse(Gate::forUser($sistema)->allows('gerir-utilizadores'));
        $this->assertTrue(Gate::forUser($super)->allows('gerir-utilizadores'));

        foreach (['processar-encomendas', 'rever-prescricoes'] as $habilidade) {
            $this->assertFalse(Gate::forUser($cliente)->allows($habilidade));
            $this->assertTrue(Gate::forUser($sistema)->allows($habilidade));
            $this->assertTrue(Gate::forUser($super)->allows($habilidade));
        }
    }
}
