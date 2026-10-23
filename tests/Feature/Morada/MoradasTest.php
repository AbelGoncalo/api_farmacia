<?php

namespace Tests\Feature\Morada;

use App\Models\Morada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class MoradasTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    /**
     * Dados válidos de uma morada.
     *
     * @return array<string, mixed>
     */
    private function dadosMorada(): array
    {
        return [
            'nome_destinatario' => 'Maria Silva',
            'telefone' => '912345678',
            'provincia' => 'Lisboa',
            'municipio' => 'Oeiras',
            'bairro' => 'Centro',
            'rua' => 'Rua das Flores',
            'numero_porta' => '12',
        ];
    }

    /**
     * Cria uma morada via API e devolve o seu id.
     */
    private function criarMoradaViaApi(array $dados = []): string
    {
        return (string) $this->postJson('/api/v1/moradas', $dados !== [] ? $dados : $this->dadosMorada())
            ->assertStatus(201)
            ->json('data.id');
    }

    public function test_primeira_morada_fica_predefinida_e_a_segunda_nao(): void
    {
        $this->autenticarCliente();

        $primeira = $this->criarMoradaViaApi();

        $this->assertTrue((bool) Morada::query()->findOrFail($primeira)->predefinida);

        $segunda = $this->criarMoradaViaApi();

        $this->assertTrue((bool) Morada::query()->findOrFail($primeira)->predefinida);
        $this->assertFalse((bool) Morada::query()->findOrFail($segunda)->predefinida);
    }

    public function test_marcar_nova_predefinida_na_criacao_limpa_a_anterior(): void
    {
        $this->autenticarCliente();

        $primeira = $this->criarMoradaViaApi();

        $this->postJson('/api/v1/moradas', array_merge($this->dadosMorada(), ['predefinida' => true]))
            ->assertStatus(201)
            ->assertJsonPath('data.predefinida', true);

        $this->assertFalse((bool) Morada::query()->findOrFail($primeira)->predefinida);
    }

    public function test_patch_marcar_como_predefinida_troca_a_predefinida(): void
    {
        $this->autenticarCliente();

        $primeira = $this->criarMoradaViaApi();
        $segunda = $this->criarMoradaViaApi();

        $this->patchJson("/api/v1/moradas/{$segunda}", ['predefinida' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.predefinida', true);

        $this->assertFalse((bool) Morada::query()->findOrFail($primeira)->predefinida);
        $this->assertTrue((bool) Morada::query()->findOrFail($segunda)->predefinida);
    }

    public function test_index_lista_apenas_moradas_proprias_e_ativas(): void
    {
        $utilizador = $this->autenticarCliente();

        $ativa = $this->criarMoradaViaApi();
        $desativada = $this->criarMoradaViaApi();
        $this->deleteJson("/api/v1/moradas/{$desativada}")->assertStatus(200);

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');
        $this->criarMoradaViaApi();

        $this->actingAs($utilizador, 'api');

        $this->getJson('/api/v1/moradas')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ativa);
    }

    public function test_show_devolve_morada_propria_e_404_para_alheia(): void
    {
        $this->autenticarCliente();
        $id = $this->criarMoradaViaApi();

        $this->getJson("/api/v1/moradas/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.ativa', true);

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $this->getJson("/api/v1/moradas/{$id}")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_show_rejeita_identificador_invalido(): void
    {
        $this->autenticarCliente();

        $this->getJson('/api/v1/moradas/nao-e-uuid')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['morada']]);
    }

    public function test_update_atualiza_campos_da_morada_propria(): void
    {
        $this->autenticarCliente();
        $id = $this->criarMoradaViaApi();

        $this->patchJson("/api/v1/moradas/{$id}", [
            'etiqueta' => 'Casa',
            'numero_porta' => '12B',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.etiqueta', 'Casa')
            ->assertJsonPath('data.numero_porta', '12B')
            ->assertJsonPath('data.nome_destinatario', 'Maria Silva');
    }

    public function test_update_e_delete_de_morada_alheia_devolvem_404(): void
    {
        $this->autenticarCliente();
        $id = $this->criarMoradaViaApi();

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $this->patchJson("/api/v1/moradas/{$id}", ['etiqueta' => 'Intruso'])
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->deleteJson("/api/v1/moradas/{$id}")
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->assertTrue((bool) Morada::query()->findOrFail($id)->ativa);
    }

    public function test_delete_desativa_preserva_a_linha_e_sai_do_checkout(): void
    {
        $this->autenticarCliente();
        $id = $this->criarMoradaViaApi();

        $this->deleteJson("/api/v1/moradas/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.ativa', false)
            ->assertJsonPath('data.predefinida', false);

        // A linha não é apagada — preserva o vínculo com encomendas antigas.
        $this->assertDatabaseHas('moradas', ['id' => $id]);

        // Deixa de aparecer no índice.
        $this->getJson('/api/v1/moradas')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // E deixa de poder ser usada no checkout.
        $produto = $this->produtoComStock(10.0);
        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $id,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['morada_id']]);
    }

    public function test_update_reativa_morada_desativada(): void
    {
        $this->autenticarCliente();
        $id = $this->criarMoradaViaApi();

        $this->deleteJson("/api/v1/moradas/{$id}")->assertStatus(200);

        $this->patchJson("/api/v1/moradas/{$id}", ['ativa' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.ativa', true);

        $this->getJson('/api/v1/moradas')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);
    }

    public function test_cria_morada_valida_campos_obrigatorios(): void
    {
        $this->autenticarCliente();

        $this->postJson('/api/v1/moradas', [])
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['nome_destinatario', 'telefone', 'provincia', 'municipio', 'bairro'],
            ]);
    }

    public function test_checkout_entrega_domicilio_com_morada_criada_na_api(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $moradaId = $this->criarMoradaViaApi();

        $this->adicionarAoCarrinho($produto, 1);

        $taxa = (float) config('encomendas.taxa_entrega_domicilio');

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $moradaId,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.morada_id', $moradaId)
            ->assertJsonPath('data.taxa_entrega', number_format($taxa, 2, '.', ''));
    }

    public function test_checkout_rejeita_morada_de_outro_utilizador(): void
    {
        $this->autenticarCliente();
        $moradaId = $this->criarMoradaViaApi();

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $produto = $this->produtoComStock(10.0);
        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $moradaId,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['morada_id']]);
    }

    public function test_moradas_requerem_autenticacao(): void
    {
        $this->getJson('/api/v1/moradas')->assertStatus(401);
        $this->postJson('/api/v1/moradas', $this->dadosMorada())->assertStatus(401);
    }
}
