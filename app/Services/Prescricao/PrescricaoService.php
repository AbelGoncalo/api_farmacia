<?php

namespace App\Services\Prescricao;

use App\Enums\Prescricao\EstadoPrescricao;
use App\Exceptions\PrescricaoException;
use App\Jobs\AnalisarPrescricao;
use App\Models\Prescricao;
use App\Models\User;
use App\Repositories\Prescricao\PrescricaoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PrescricaoService
{
    public function __construct(
        private readonly PrescricaoRepository $prescricaoRepository,
    ) {}

    /**
     * Submete uma prescrição: guarda o documento em disco privado e cria o
     * registo em estado PENDENTE para posterior revisão por um profissional.
     *
     * O nome original do ficheiro nunca é guardado (privacidade): o documento
     * é armazenado com um nome aleatório e extensão derivada do conteúdo.
     *
     * @throws PrescricaoException quando o documento não pode ser guardado.
     */
    public function submeter(User $utilizador, UploadedFile $ficheiro): Prescricao
    {
        $caminho = $ficheiro->store('prescricoes/'.$utilizador->id, ['disk' => 'local']);

        if ($caminho === false) {
            throw new PrescricaoException(
                'Não foi possível guardar o documento da prescrição.',
                500,
                ['ficheiro' => ['Não foi possível guardar o documento da prescrição.']],
            );
        }

        try {
            $prescricao = $this->prescricaoRepository->criarPrescricao([
                'id' => (string) Str::uuid(),
                'utilizador_id' => $utilizador->id,
                'caminho_ficheiro' => $caminho,
                'tipo_ficheiro' => strtoupper($ficheiro->guessExtension() ?? 'DESCONHECIDO'),
                'estado' => EstadoPrescricao::PENDENTE->value,
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($caminho);

            throw $e;
        }

        AnalisarPrescricao::dispatch($prescricao->id)->afterCommit();

        return $prescricao;
    }

    /**
     * Lista as prescrições do utilizador, de forma paginada.
     */
    public function listar(User $utilizador, int $porPagina): LengthAwarePaginator
    {
        return $this->prescricaoRepository->listarPorUtilizador($utilizador->id, $porPagina);
    }

    /**
     * Procura uma prescrição do utilizador, ou null caso não pertença a ele.
     */
    public function buscar(User $utilizador, string $id): ?Prescricao
    {
        return $this->prescricaoRepository->buscarDoUtilizador($id, $utilizador->id);
    }

    /**
     * Devolve o caminho do documento da prescrição do utilizador, quando o
     * registo e o ficheiro existem; caso contrário, null.
     */
    public function caminhoDocumento(User $utilizador, string $id): ?string
    {
        $caminho = $this->prescricaoRepository->caminhoDoUtilizador($id, $utilizador->id);

        if ($caminho === null || ! Storage::disk('local')->exists($caminho)) {
            return null;
        }

        return $caminho;
    }
}
