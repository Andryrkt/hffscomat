<?php

namespace App\Model\magasin\Commande\Livrer;

use App\Dto\Magasin\Commande\Livrer\CommandeLivrerSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class CommandeLivrerModel extends Model
{
    public function recupereListeCommandeLivrer(CommandeLivrerSearchDto $dtoSearch): array
    {
        return [];
    }
}
