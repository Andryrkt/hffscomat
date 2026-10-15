<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Controller\Traits\DitListeTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Model\Atelier\Dit\DitModel;
use App\Service\docuware\CopyDocuwareService;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\SuiviOperation;
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

        $numeroDit = $ditInformations["numero_demande_dit"];
        $historiqueOperation = new HistoriqueOperationDITService($this->getEntityManager());

        // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
        $suivi = new SuiviOperation();
        $message = "La DIT a été clôturé avec succès.";

        try {
            $suivi->etape('Mise à jour de la DIT', function () use ($numeroDit, $ditInformations) {
                $this->modificationDit($numeroDit, $ditInformations["code_societe"]);
            });

            $fileNameUplode = 'fichier_cloturer_annuler_' . $numeroDit . '.csv';
            $basePath = rtrim($_ENV['BASE_PATH_FICHIER'], '/\\');

            $dirPath = $basePath . '/dit/' . $numeroDit;
            $filePathUplode = $dirPath . '/' . $fileNameUplode;

            $fileNameDw = $fileNameUplode;
            // $filePathDw = $_ENV['BASE_PATH_FICHIER'] . '/dit/csv/' . $fileNameDw;

            $suivi->etape('Génération fichier CSV', function () use ($dirPath, $filePathUplode) {
                // create directory if not exists
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0777, true);
                }

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
            });

            // un échec de copie ne bloque pas la clôture (déjà enregistrée) : il est signalé dans le message
            try {
                $suivi->etape('Copie FTP DocuWare', function () use ($fileNameDw, $filePathUplode) {
                    if (!(new CopyDocuwareService())->copyCsvToDw($fileNameDw, $filePathUplode)) {
                        throw new \RuntimeException("copie du fichier $fileNameDw vers le FTP DocuWare échouée (voir le log)");
                    }
                });
            } catch (EtapeOperationException $e) {
                $message = "La DIT a été clôturé, mais l'envoi du fichier vers DocuWare a échoué.";
            }
        } catch (EtapeOperationException $e) {
            $message = "Echec de la clôture de la DIT " . $numeroDit . " à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
            $historiqueOperation->setEtapes($suivi->getEtapes());
            $historiqueOperation->sendNotificationCloture($message, $numeroDit, 'dit_liste', false);
        }

        $historiqueOperation->setEtapes($suivi->getEtapes());
        $historiqueOperation->sendNotificationCloture($message, $numeroDit, 'dit_liste', true);
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
