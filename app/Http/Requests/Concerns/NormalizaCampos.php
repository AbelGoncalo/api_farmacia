<?php

namespace App\Http\Requests\Concerns;

trait NormalizaCampos
{
    /** @param list<string> $campos */
    private function normalizarCampos(array $campos): void
    {
        foreach ($campos as $campo) {
            $valor = $this->input($campo);

            if (is_string($valor)) {
                $this->merge([$campo => trim($valor)]);
            }
        }

        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower($email)]);
        }
    }
}
