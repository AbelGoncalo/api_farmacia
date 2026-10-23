<?php

namespace App\Repositories\Morada;

use App\Models\Morada;
use Illuminate\Database\Eloquent\Collection;

class MoradaRepository
{
    /**
     * Lista as moradas ativas do utilizador, com a predefinida primeiro.
     *
     * @return Collection<int, Morada>
     */
    public function listarAtivasDoUtilizador(int $utilizadorId): Collection
    {
        return Morada::query()
            ->where('utilizador_id', $utilizadorId)
            ->where('ativa', true)
            ->orderByDesc('predefinida')
            ->orderByDesc('criado_em')
            ->get();
    }

    /**
     * Procura uma morada do utilizador, ou null se não existir
     * ou pertencer a outro utilizador.
     */
    public function buscarDoUtilizador(string $id, int $utilizadorId): ?Morada
    {
        return Morada::query()
            ->where('id', $id)
            ->where('utilizador_id', $utilizadorId)
            ->first();
    }

    /**
     * Conta as moradas ativas do utilizador — usado para decidir
     * se a primeira morada criada fica predefinida por omissão.
     */
    public function contarAtivasDoUtilizador(int $utilizadorId): int
    {
        return Morada::query()
            ->where('utilizador_id', $utilizadorId)
            ->where('ativa', true)
            ->count();
    }

    /**
     * Cria a morada do utilizador a partir dos dados preparados pelo serviço.
     *
     * @param  array{
     *     id: string, utilizador_id: int, etiqueta?: ?string, nome_destinatario: string,
     *     telefone: string, provincia: string, municipio: string, bairro: string,
     *     rua?: ?string, numero_porta?: ?string, referencia?: ?string, predefinida: bool
     * }  $dados
     */
    public function criar(array $dados): Morada
    {
        $morada = new Morada;
        $morada->id = $dados['id'];
        $morada->utilizador_id = $dados['utilizador_id'];
        $morada->etiqueta = $dados['etiqueta'] ?? null;
        $morada->nome_destinatario = $dados['nome_destinatario'];
        $morada->telefone = $dados['telefone'];
        $morada->provincia = $dados['provincia'];
        $morada->municipio = $dados['municipio'];
        $morada->bairro = $dados['bairro'];
        $morada->rua = $dados['rua'] ?? null;
        $morada->numero_porta = $dados['numero_porta'] ?? null;
        $morada->referencia = $dados['referencia'] ?? null;
        $morada->predefinida = $dados['predefinida'];
        $morada->ativa = true;
        $morada->save();

        return $morada;
    }

    /**
     * Remove a marcação de predefinida de todas as moradas do utilizador.
     *
     * Corre dentro da transação que marca uma nova predefinida, para
     * garantir a unicidade (a tabela tem unique stored em MySQL).
     */
    public function limparPredefinidas(int $utilizadorId): void
    {
        Morada::query()
            ->where('utilizador_id', $utilizadorId)
            ->where('predefinida', true)
            ->update([
                'predefinida' => false,
                'atualizado_em' => now(),
            ]);
    }

    /**
     * Aplica os campos validados à morada.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Morada $morada, array $dados): void
    {
        foreach ($dados as $campo => $valor) {
            $morada->{$campo} = $valor;
        }

        $morada->save();
    }

    /**
     * Desativa a morada sem apagar a linha, preservando o vínculo
     * com encomendas antigas e a morada predefinida deixa de ser.
     */
    public function desativar(Morada $morada): void
    {
        $morada->ativa = false;
        $morada->predefinida = false;
        $morada->save();
    }
}
