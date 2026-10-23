<?php

namespace App\Services\Morada;

use App\Models\Morada;
use App\Models\User;
use App\Repositories\Morada\MoradaRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gestão das moradas de entrega do utilizador.
 *
 * Apenas o próprio utilizador gere as suas moradas: todos os métodos
 * filtram por posse, pelo que moradas de outros utilizadores devolvem
 * null (404, sem expor existência).
 *
 * A morada predefinida é única por utilizador. A troca é feita dentro
 * de uma transação (limpar as outras antes de marcar a nova), com a
 * unique stored da tabela como backstop em MySQL.
 */
class MoradaService
{
    /**
     * Campos que podem ser alterados por um pedido do cliente.
     * Nunca inclui id nem utilizador_id (proteção contra mass assignment).
     */
    private const CAMPOS_EDITAVEIS = [
        'etiqueta',
        'nome_destinatario',
        'telefone',
        'provincia',
        'municipio',
        'bairro',
        'rua',
        'numero_porta',
        'referencia',
        'predefinida',
        'ativa',
    ];

    public function __construct(
        private readonly MoradaRepository $moradaRepository,
    ) {}

    /**
     * Lista as moradas ativas do utilizador autenticado.
     *
     * @return Collection<int, Morada>
     */
    public function listar(User $utilizador): Collection
    {
        return $this->moradaRepository->listarAtivasDoUtilizador($utilizador->id);
    }

    /**
     * Cria uma morada para o utilizador.
     *
     * A primeira morada ativa fica predefinida por omissão; nas restantes,
     * a marcação `predefinida` limpa as outras dentro da transação.
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(User $utilizador, array $dados): Morada
    {
        return DB::transaction(function () use ($utilizador, $dados): Morada {
            $primeira = $this->moradaRepository->contarAtivasDoUtilizador($utilizador->id) === 0;
            $predefinida = $primeira || ! empty($dados['predefinida']);

            if ($predefinida) {
                $this->moradaRepository->limparPredefinidas($utilizador->id);
            }

            return $this->moradaRepository->criar([
                'id' => (string) Str::uuid(),
                'utilizador_id' => $utilizador->id,
                'etiqueta' => $dados['etiqueta'] ?? null,
                'nome_destinatario' => $dados['nome_destinatario'],
                'telefone' => $dados['telefone'],
                'provincia' => $dados['provincia'],
                'municipio' => $dados['municipio'],
                'bairro' => $dados['bairro'],
                'rua' => $dados['rua'] ?? null,
                'numero_porta' => $dados['numero_porta'] ?? null,
                'referencia' => $dados['referencia'] ?? null,
                'predefinida' => $predefinida,
            ]);
        });
    }

    /**
     * Procura uma morada do próprio utilizador, ou null caso
     * não exista ou pertença a outro.
     */
    public function buscar(User $utilizador, string $id): ?Morada
    {
        return $this->moradaRepository->buscarDoUtilizador($id, $utilizador->id);
    }

    /**
     * Atualiza uma morada do próprio utilizador com os campos validados.
     *
     * Marcar `predefinida` limpa as outras moradas do utilizador dentro
     * da transação; `ativa` permite reativar uma morada desativada.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(User $utilizador, string $id, array $dados): ?Morada
    {
        $campos = array_intersect_key($dados, array_flip(self::CAMPOS_EDITAVEIS));

        return DB::transaction(function () use ($utilizador, $id, $campos): ?Morada {
            $morada = $this->moradaRepository->buscarDoUtilizador($id, $utilizador->id);

            if ($morada === null) {
                return null;
            }

            if (! empty($campos['predefinida'])) {
                $this->moradaRepository->limparPredefinidas($utilizador->id);
            }

            $this->moradaRepository->atualizar($morada, $campos);

            return $morada;
        });
    }

    /**
     * Desativa uma morada do próprio utilizador (não apaga a linha,
     * para preservar o vínculo com encomendas antigas).
     */
    public function desativar(User $utilizador, string $id): ?Morada
    {
        return DB::transaction(function () use ($utilizador, $id): ?Morada {
            $morada = $this->moradaRepository->buscarDoUtilizador($id, $utilizador->id);

            if ($morada === null) {
                return null;
            }

            $this->moradaRepository->desativar($morada);

            return $morada;
        });
    }
}
