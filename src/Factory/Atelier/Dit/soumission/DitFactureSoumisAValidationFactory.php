<?php

namespace App\Factory\Atelier\Dit\soumission;

use App\Dto\atelier\dit\soumission\DitFactureSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\DitFactureSoumisAValidationModel;
use App\Service\security\SecurityService;
use Symfony\Component\Form\FormInterface;

class DitFactureSoumisAValidationFactory
{
    public function initialisation(string $numDit, SecurityService $securityService): DitFactureSoumisAValidationDto
    {
        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();
        $dto = new DitFactureSoumisAValidationDto();
        $dto->numeroDit = $numDit;
        $dto->codeSociete = $securityService->getCodeSocieteUser();
        $dto->numeroOr = $ditFactureSoumisAValidationModel->recupNumeroOr($dto->numeroDit, $dto->codeSociete);

        return $dto;
    }


    public function apresSoumission(DitFactureSoumisAValidationDto $dto, FormInterface $form, string $numDit): DitFactureSoumisAValidationDto
    {
        $numFac = explode('_', $form->get("pieceJoint01")->getData()->getClientOriginalName());

        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();

        $dto->numeroDit = $numDit;
        $dto->numeroSoumission = $ditFactureSoumisAValidationModel->recupNumeroSoumission($dto->numeroOr, $dto->codeSociete);
        $dto->numeroFact = $numFac[1] ?? null;
        $dto->dateSoumission = date('Y-m-d');
        $dto->heureSoumission = date('H:i');
        $agSevDebAndIntExt = $ditFactureSoumisAValidationModel->recupAgServDebAndIntExtDit($dto->numeroDit, $dto->codeSociete);

        $dto->agServDebDit = $agSevDebAndIntExt['agservdeb'];
        $dto->interneExterne = $agSevDebAndIntExt['int_ext'];
        $dto->migration = $agSevDebAndIntExt['migration'];
        $dto->infoFac = $dto->numeroFact ? $ditFactureSoumisAValidationModel->recupInfoFact($dto->numeroOr, $dto->numeroFact, $dto->codeSociete) : [];
        $dto->estRi = $this->estRi($dto, $ditFactureSoumisAValidationModel);
        $dto->etatOr = $this->etatOr($dto);
        $dto->numDevis = $ditFactureSoumisAValidationModel->recupererNumdevis($dto->numeroOr, $dto->codeSociete);

        return $dto;
    }

    private function estRi(DitFactureSoumisAValidationDto $dto, DitFactureSoumisAValidationModel $ditFactureSoumisAValidationModel)
    {
        $estRi = false;
        $riSoumis = $ditFactureSoumisAValidationModel->recupNumeroItvDejaSoumi($dto->numeroOr, $dto->codeSociete);

        if (empty($riSoumis)) {
            $estRi = true;
        } else {
            for ($i = 0; $i < count($dto->infoFac); $i++) {
                if (!in_array($dto->infoFac[$i]['numeroitv'], $riSoumis)) {
                    $estRi = true;
                    break;
                }
            }
        }
        return $estRi;
    }

    private function etatOr(DitFactureSoumisAValidationDto $dto): string
    {
        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();
        $etatFac = $ditFactureSoumisAValidationModel->recupEtatOr($dto->numeroOr, $dto->codeSociete)[0];

        if ($etatFac == 'PF') {
            return 'Partiellement facturé';
        } else {
            return 'Complètement facturé';
        }
    }
}
