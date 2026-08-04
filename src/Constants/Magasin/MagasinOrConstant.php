<?php

namespace App\Constants\Magasin;

class MagasinOrConstant
{
    public const COMPLET = "complet";
    public const INCOMPLET = "non_complet";

    public const ETATS_COMPLETUDE = [
        'TOUS'       => null,
        'COMPLETS'   => self::COMPLET,
        'INCOMPLETS' => self::INCOMPLET
    ];
}
