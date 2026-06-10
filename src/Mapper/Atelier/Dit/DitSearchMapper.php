<?php

namespace App\Mapper\Atelier\Dit;

use App\Dto\Atelier\Dit\DitSearchDto;

class DitSearchMapper
{
    /**
     * Maps criteria to a DitSearchDto.
     * * @param array|object $criteria
     * @return DitSearchDto
     */
    public function fromArray($criteria): DitSearchDto
    {
        if (is_object($criteria)) {
            $criteria = (array) $criteria;
        }

        $dto = new DitSearchDto();

        $dto->typeDocument     = $criteria["typeDocument"] ?? null;
        $dto->niveauUrgence    = $criteria["niveauUrgence"] ?? null;
        $dto->statut           = $criteria["statut"] ?? null;
        $dto->internetExterne  = $criteria["interneExterne"] ?? null;
        $dto->dateDebut        = $criteria["dateDebut"] ?? null;
        $dto->dateFin          = $criteria["dateFin"] ?? null;
        $dto->idMateriel       = $criteria["idMateriel"] ?? null;
        $dto->numParc          = $criteria["numParc"] ?? null;
        $dto->numSerie         = $criteria["numSerie"] ?? null;
        $dto->agenceEmetteur   = $criteria["agenceEmetteur"] ?? null;
        $dto->serviceEmetteur  = $criteria["serviceEmetteur"] ?? null;
        $dto->agenceDebiteur   = $criteria["agenceDebiteur"] ?? null;
        $dto->serviceDebiteur  = $criteria["serviceDebiteur"] ?? null;
        $dto->numDit           = $criteria["numDit"] ?? null;
        $dto->numOr            = $criteria["numOr"] ?? null;
        $dto->statutOr         = $criteria["statutOr"] ?? null;
        $dto->ditSansOr        = $criteria["ditSansOr"] ?? false;
        $dto->categorie        = $criteria["categorie"] ?? null;
        $dto->utilisateur      = $criteria["utilisateur"] ?? null;
        $dto->sectionAffectee  = $criteria["sectionAffectee"] ?? null;
        $dto->sectionSupport1  = $criteria["sectionSupport1"] ?? null;
        $dto->sectionSupport2  = $criteria["sectionSupport2"] ?? null;
        $dto->sectionSupport3  = $criteria["sectionSupport3"] ?? null;
        $dto->etatFacture      = $criteria["etatFacture"] ?? null;

        return $dto;
    }
}
