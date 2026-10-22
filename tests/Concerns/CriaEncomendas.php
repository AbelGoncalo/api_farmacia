<?php

namespace Tests\Concerns;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Helpers reutilizáveis para criar encomendas de teste via API
 * (autenticação, produtos e checkout).
 */
trait CriaEncomendas
{
    /**
     * Cria um cliente e autentica-o no guard `api`.
     */
    protected function autenticarCliente(): User
    {
        $utilizador = User::factory()->create([
            'papel' => 'CLIENTE',
            'estado' => 'ATIVO',
        ]);

        $this->actingAs($utilizador, 'api');

        return $utilizador;
    }

    /**
     * Cria um operador (utilizador de sistema) e autentica-o no guard `api`.
     */
    protected function autenticarOperador(): User
    {
        $operador = User::factory()->create([
            'papel' => 'UTILIZADOR_SISTEMA',
            'estado' => 'ATIVO',
        ]);

        $this->actingAs($operador, 'api');

        return $operador;
    }

    /**
     * Cria um produto disponível com o stock indicado e preço fixo de 10.00.
     */
    protected function produtoComStock(float $stock): Produto
    {
        return Produto::factory()->create([
            'categoria_id' => null,
            'ativo' => true,
            'disponivel_online' => true,
            'quantidade_stock' => $stock,
            'preco' => 10.00,
        ]);
    }

    /**
     * Adiciona uma quantidade de um produto ao carrinho do utilizador autenticado.
     */
    protected function adicionarAoCarrinho(Produto $produto, int $quantidade): void
    {
        $this->postJson('/api/v1/carrinho/itens', [
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
        ])->assertStatus(201);
    }

    /**
     * Insere uma morada do utilizador na base de dados e devolve o respetivo id.
     */
    protected function criarMorada(User $utilizador): string
    {
        $id = (string) Str::uuid();

        DB::table('moradas')->insert([
            'id' => $id,
            'utilizador_id' => $utilizador->id,
            'nome_destinatario' => 'Maria Silva',
            'telefone' => '912345678',
            'provincia' => 'Lisboa',
            'municipio' => 'Lisboa',
            'bairro' => 'Centro',
            'predefinida' => true,
            'ativa' => true,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        return $id;
    }

    /**
     * Executa o checkout do carrinho e devolve o id da encomenda criada.
     *
     * @param  array<string, mixed>  $dados  dados extra do pedido (morada, observações, ...).
     */
    protected function criarEncomenda(Produto $produto, int $quantidade = 1, array $dados = []): string
    {
        $this->adicionarAoCarrinho($produto, $quantidade);

        return $this->postJson('/api/v1/encomendas', array_merge(
            ['tipo_entrega' => 'RECOLHA_LOJA'],
            $dados,
        ))
            ->assertStatus(201)
            ->json('data.id');
    }
}
