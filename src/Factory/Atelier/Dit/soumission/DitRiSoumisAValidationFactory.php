<?php

namespace App\Factory\Atelier\Dit\soumission;

use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\DitRiSoumisAValidationModel;
use App\Service\security\SecurityService;
use Symfony\Component\Form\FormInterface;

class DitRiSoumisAValidationFactory
{
    public function initialisation(string $numDit, SecurityService $securityService): DitRiSoumisAValidationDto
    {
        $ditRiSoumisAValidationModel = new DitRiSoumisAValidationModel();
        $dto = new DitRiSoumisAValidationDto();
        $dto->numeroDit = $numDit;
        $dto->codeSociete = $securityService->getCodeSocieteUser();
        $dto->numeroOr = $ditRiSoumisAValidationModel->recupNumeroOr($dto->numeroDit, $dto->codeSociete);
        $dto->itvDejaSoumis = $ditRiSoumisAValidationModel->findItvDejaSoumis($dto->numeroOr, $dto->codeSociete);
        $dto->itvAfficher = $ditRiSoumisAValidationModel->recupInterventionOr($dto->numeroOr, $dto->itvDejaSoumis, $dto->codeSociete);

        return $dto;
    }

    public function apresSoumission(DitRiSoumisAValidationDto $dto, FormInterface $form): DitRiSoumisAValidationDto
    {
        $ditRiSoumisAValidationModel = new DitRiSoumisAValidationModel();
        $dto->dateSoumission = date('Y-m-d');
        $dto->heureSoumission = date('H:i');
        $dto->numeroSoumission = $ditRiSoumisAValidationModel->recupNumeroSoumission($dto->numeroOr, $dto->codeSociete);
        $dto->itvCoches = $this->itvCocher($dto->itvAfficher, $form);
        $dto->tousNumeroItv = $ditRiSoumisAValidationModel->recupToutNumeroItv($dto->numeroOr, $dto->codeSociete);
        [$existe, $estSoumis] = $this->numeroItvEstExisteEtSoumis($dto);
        $dto->existe = $existe;
        $dto->estSoumis = $estSoumis;
        return $dto;
    }

    private function itvCocher(array $itvAfficher, FormInterface $form): array
    {
        $itvCoches = [];

        for ($i = 0; $i < count($itvAfficher); $i++) {
            $checkboxFieldName = 'checkbox_' . $i;
            if ($form->has($checkboxFieldName) && $form->get($checkboxFieldName)->getData()) {
                $itvCoches[] = (int)$itvAfficher[$i]['numeroitv'];
            }
        }
        return $itvCoches;
    }

    private function numeroItvEstExisteEtSoumis(DitRiSoumisAValidationDto $dto): array
    {
        $existe = false;
        $estSoumis = false;
        foreach ($dto->itvCoches as $value) {
            if (in_array($value, $dto->itvDejaSoumis)) {
                $estSoumis = true;
                break;
            }
            if (!in_array($value, $dto->tousNumeroItv)) {
                $existe = true;
            }
        }

        return [$existe, $estSoumis];
    }
}
