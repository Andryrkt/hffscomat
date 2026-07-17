<?php

namespace App\Api\Atelier\Dit;

use App\Controller\Controller;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use Symfony\Component\Routing\Annotation\Route;

class DocSoumisDwApi extends Controller
{
    /**
     * @Route("/api/constraint-soumission/{numDit}", name="api_constraint_soumission")
     *
     * @param string $numDit
     * @return void
     */
    public function constraintSoumission($numDit)
    {
        $constraitSoumission = $this->recupConstrainte($numDit);

        header("Content-type:application/json");

        echo json_encode($constraitSoumission);
    }

    private function recupConstrainte(string $numDit): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $ditModel = new DitModel();
        $constraitDevis = $ditModel->recupeConstraintSoumission($numDit, $codeSociete);


        $devisModel = new DitDevisSoumisAValidationModel();
        $statutDevis = $devisModel->findStatutDevis($numDit, $codeSociete);
        $numDevis = $devisModel->recupNumeroDevis($numDit, $codeSociete);

        $ditOrsomisAValidationModel = new DitOrSoumisAValidationModel();
        $numOrBaseDonner = $ditOrsomisAValidationModel->recupNumeroOr($numDit, $codeSociete);

        if (empty($constraitDevis)) {
            $client = "";
            $statutDit = "";
        } else {
            $client = $constraitDevis[0]['client'];
            $statutDit = $constraitDevis[0]['statut'];
        }

        // DEVIS-VP (vérification de prix) ne s'affiche que si l'OR contient
        // au moins une ligne d'article de stock (abse_codg = 'ST')
        $afficherVerifPrix = true;
        if ($numDevis !== null) {
            $nbrArticleVerifPrix = $ditOrsomisAValidationModel->countArticleVerificationPrix($numDevis, $codeSociete);
            $afficherVerifPrix = $nbrArticleVerifPrix > 0;
        }

        return  [
            "client" => $client,
            "statutDit" => $statutDit,
            "statutDevis" => $statutDevis,
            "numeroOR" => $numOrBaseDonner,
            "afficherVerifPrix" => $afficherVerifPrix
        ];
    }
}
