<?php

namespace App\Constants\Magasin;

class MagasinOrConstant
{
    public const TOUS = "tous";
    public const COMPLET = "complet";
    public const INCOMPLET = "non_complet";

    public const ETATS_COMPLETUDE = [
        'TOUS'       => self::TOUS,
        'COMPLETS'   => self::COMPLET,
        'INCOMPLETS' => self::INCOMPLET
    ];
}
