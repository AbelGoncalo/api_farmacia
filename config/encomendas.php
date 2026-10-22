<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taxa de entrega ao domicílio
    |--------------------------------------------------------------------------
    |
    | Valor, em euros, cobrado quando o tipo de entrega é ENTREGA_DOMICILIO.
    | A recolha na loja (RECOLHA_LOJA) nunca é taxa.
    |
    */

    'taxa_entrega_domicilio' => (float) env('TAXA_ENTREGA_DOMICILIO', 2.50),

];
