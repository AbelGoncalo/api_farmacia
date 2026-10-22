<?php

namespace App\Exceptions;

use Exception;

class EncomendaException extends Exception
{
    /**
     * Cria uma exceção de negócio do domínio de encomendas.
     *
     * @param  array<string, list<string>>  $errors  erros de validação associados, por campo.
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /**
     * Retorna o status HTTP associado à exceção.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Retorna os erros de validação associados à exceção.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
