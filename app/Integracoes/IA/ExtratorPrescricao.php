<?php

namespace App\Integracoes\IA;

/**
 * Provedor de extração de prescrições (ponto único de integração com IA).
 *
 * O provedor apenas EXTRAI e SUGERE: nunca aprova, nunca autoriza vendas,
 * nunca inventa dados e nunca substitui medicamentos. A aprovação é sempre
 * manual por um profissional autorizado (gate rever-prescricoes).
 */
interface ExtratorPrescricao
{
    /**
     * Extrai os itens do documento a partir do seu conteúdo binário.
     *
     * @param  string  $conteudo  conteúdo binário do ficheiro (PDF ou imagem)
     * @param  string  $tipoFicheiro  tipo derivado do conteúdo (ex.: PDF, PNG)
     *
     * @throws \RuntimeException quando o documento não pode ser analisado
     */
    public function extrair(string $conteudo, string $tipoFicheiro): ResultadoExtracao;
}
