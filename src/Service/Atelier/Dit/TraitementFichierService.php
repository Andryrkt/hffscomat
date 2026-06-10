<?php

namespace App\Service\atelier\dit;

use App\Controller\Traits\FormatageTrait;
use App\Controller\Traits\PdfConversionTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Model\Atelier\Dit\DitModel;
use App\Service\atelier\dit\fichier\DitNameFileService;
use App\Service\fichier\TraitementDeFichier;
use App\Service\fichier\UploderFileService;
use App\Service\genererPdf\dit\GenererPdfDit;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TraitementFichierService
{
    use PdfConversionTrait;
    use FormatageTrait;

    public function traitementDeFichier(FormInterface $form, DitDto $dto): array
    {
        /** 
         * gestion des pieces jointes et generer le nom du fichier PDF
         * Enregistrement de fichier uploder
         * @var array $nomEtCheminFichiersEnregistrer 
         * @var array $nomFichierEnregistrer 
         * @var string $nomAvecCheminFichier
         * @var string $nomFichier
         */
        [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier] = $this->enregistrementFichier($form, $dto->numeroDemandeIntervention, str_replace("-", "", $dto->agenceServiceEmetteur));

        /** 1. CREATION DE LA PAGE DE GARDE*/
        $ditModel = new DitModel();
        $idMateriel = (int)$dto->idMateriel;
        if (!in_array($idMateriel, $ditModel->getNumeroMatriculePasMateriel())) {
            //récupération des historique de materiel (informix)
            $historiqueMateriel = $this->historiqueInterventionMateriel($idMateriel);
        } else {
            $historiqueMateriel = [];
        }
        $genererPdfDit = new GenererPdfDit();
        $genererPdfDit->genererPdfDit($dto, $historiqueMateriel, $nomAvecCheminFichier);

        // 2. ajout du page de garde à la premier position
        $traitementDeFichier = new TraitementDeFichier();
        $nomEtCheminFichiersEnregistrer = $traitementDeFichier->insertFileAtPosition($nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, 0);
        // 3. fusion du page de garde et des pieces jointes (conversion avant la fusion)
        $nomEtCheminFichierConvertie = $this->ConvertirLesPdf($nomEtCheminFichiersEnregistrer);
        $traitementDeFichier->fusionFichers($nomEtCheminFichierConvertie, $nomAvecCheminFichier);

        return [$nomFichierEnregistrer, $nomFichier];
    }

    private function historiqueInterventionMateriel(int $idMateriel): array
    {
        $ditModel = new DitModel();
        $historiqueMateriel = $ditModel->historiqueMateriel($idMateriel);

        foreach ($historiqueMateriel as $keys => $values) {
            foreach ($values as $key => $value) {
                if ($key == "datedebut") {
                    $historiqueMateriel[$keys]['datedebut'] = implode('/', array_reverse(explode("-", $value)));
                } elseif ($key === 'somme') {
                    $historiqueMateriel[$keys][$key] = explode(',', $this->formatNumber($value))[0];
                }
            }
        }
        return $historiqueMateriel;
    }

    private function enregistrementFichier(FormInterface $form, string $numDit, string $agServEmetteur): array
    {
        $nameGenerator = new DitNameFileService();
        $cheminBaseUpload = $_ENV['BASE_PATH_FICHIER'] . '/dit/';
        $uploader = new UploderFileService($cheminBaseUpload, $nameGenerator);
        $path = $cheminBaseUpload . $numDit . '/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        /**
         * recupère les noms + chemins dans un tableau et les noms dans une autre
         * @var array $nomEtCheminFichiersEnregistrer
         * @var array $nomFichierEnregistrer
         */
        [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer] = $uploader->getFichiers($form, [
            'repertoire' => $path,
            'generer_nom_callback' => function (
                UploadedFile $file,
                int $index
            ) use ($numDit, $nameGenerator, $agServEmetteur) {
                return $nameGenerator->generateDitNameFile($file, $numDit, $agServEmetteur, $index);
            }
        ]);

        $nomFichier = $nameGenerator->generateDitNamePrincipal($numDit, $agServEmetteur);
        $nomAvecCheminFichier = $path . $nomFichier;

        return [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier];
    }
}
