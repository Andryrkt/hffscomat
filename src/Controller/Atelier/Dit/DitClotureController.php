<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Controller\Traits\DitListeTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Model\Atelier\Dit\DitModel;
use App\Service\docuware\CopyDocuwareService;
use App\Service\historiqueOperation\HistoriqueOperationORService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitClotureController extends Controller
{
    private DitModel $ditModel;



    public function __construct()
    {
        parent::__construct();
        // $this->historiqueOperation      = new HistoriqueOperationORService($this->getEntityManager());
        $this->ditModel = new DitModel();
    }

    /**
     * @Route("/cloturer-annuler/{numDit}", name="api_cloturer_annuler_dit_liste")
     */
    public function clotureStatut($numDit)
    {

        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $ditModel = new DitModel();
        $ditInformations = $ditModel->recupInformationsDit($numDit, $codeSociete);

        if (empty($ditInformations) || !isset($ditInformations['numero_demande_dit'])) {
            $this->notification("DIT introuvable ou inexistante.");
            return $this->redirectToRoute("dit_liste");
        }

        $this->modificationDit($ditInformations["numero_demande_dit"], $ditInformations["code_societe"]);

        $fileNameUplode = 'fichier_cloturer_annuler_' . $ditInformations["numero_demande_dit"] . '.csv';
        $basePath = rtrim($_ENV['BASE_PATH_FICHIER'], '/\\');

        $dirPath = $basePath . '/dit/' . $ditInformations["numero_demande_dit"];

        // create directory if not exists
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $filePathUplode = $dirPath . '/' . $fileNameUplode;

        $fileNameDw = $fileNameUplode;
        // $filePathDw = $_ENV['BASE_PATH_FICHIER'] . '/dit/csv/' . $fileNameDw;
        $headers = ['Numéro DIT', 'statut'];
        $numDits = (new DitModel())->recupDitAAnnuler();

        $data = [];

        foreach ($numDits as  $numDit) {
            $data[] = [
                $numDit["numero_demande_dit"],
                'Clôturé annulé'
            ];
        }

        if (file_exists($filePathUplode)) {
            unlink($filePathUplode);
        }

        $this->ajouterDansCsv($filePathUplode, $data, $headers);
        $copyDocuwareService = new CopyDocuwareService();
        $copyDocuwareService->copyCsvToDw($fileNameDw, $filePathUplode);

        $message = "La DIT a été clôturé avec succès.";
        $this->notification($message);
        $this->redirectToRoute("dit_liste");
    }




    private function modificationDit($numDit, $codeSociete)
    {
        $this->ditModel->updateStatutDateAnnuler($numDit, $codeSociete);
    }

    private function ajouterDansCsv($filePath, $data, $headers = null)
    {
        $fichierExiste = file_exists($filePath);
        $handle = fopen($filePath, 'a');

        // Si le fichier est nouveau, ajoute un BOM UTF-8
        if (!$fichierExiste) {
            fwrite($handle, "\xEF\xBB\xBF"); // Ajout du BOM
        }

        // Fonction pour écrire une ligne sans guillemets
        $ecrireLigne = function ($ligne) use ($handle) {
            $ligneUtf8 = array_map(function ($field) {
                if (is_array($field)) {
                    // Tu peux choisir un séparateur ou une structure ici
                    $field = implode(';', $field);
                }
                return mb_convert_encoding($field, 'UTF-8');
            }, $ligne);
            fwrite($handle, implode(';', $ligneUtf8) . PHP_EOL); // tu peux changer ';' par ',' si nécessaire
        };
        // Écrit les en-têtes si le fichier est nouveau
        if (!$fichierExiste && $headers !== null) {
            $ecrireLigne($headers);
        }

        // Écrit les données sans guillemets
        foreach ($data as $ligne) {
            $ecrireLigne($ligne);
        }

        fclose($handle);
    }

    private function notification($message)
    {
        $this->getSessionService()->set('notification', ['type' => 'success', 'message' => $message]);
    }
}
