<?php

namespace App\Service\atelier\dit\soumission\Ri;

use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;
use App\Service\fichier\TraitementDeFichier;
use App\Service\historiqueOperation\Atelier\Dit\Ri\HistoriqueOperationRIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TraitementFichierService
{
    private HistoriqueOperationRIService $historiqueOperation;
    private string $cheminDeBase;
    private TraitementDeFichier $traitementDeFichier;

    public function __construct(EntityManagerInterface $em)
    {
        $this->historiqueOperation = new HistoriqueOperationRIService($em);
        $this->cheminDeBase = $_ENV['BASE_PATH_FICHIER'] . '/vri/';
        $this->traitementDeFichier = new TraitementDeFichier();
    }

    public function traiterFichierJoint(FormInterface $form, DitRiSoumisAValidationDto $dto): array
    {
        $file = $form->get("pieceJoint01")->getData();
        $nomDesFichiers = [];

        if (!$file) {
            $this->historiqueOperation->sendNotificationSoumission(
                'Aucun fichier n\'a été sélectionné.',
                '-',
                'dit_liste'
            );
            return $nomDesFichiers;
        }

        // Validation du fichier
        if (!$this->validerFichier($file)) {
            return $nomDesFichiers;
        }

        if (empty($dto->itvCoches)) {
            return $nomDesFichiers;
        }

        // On prend le premier ITV pour faire l'upload initial
        $firstItv = $dto->itvCoches[0];
        $originalFileName = '';

        try {
            $originalFileName = $this->genererNomFichier($dto->numeroOr, $firstItv, $file);
            $this->traitementDeFichier->upload($file, $this->cheminDeBase, $originalFileName);
            $nomDesFichiers[] = $originalFileName;
            $sourcePath = $this->cheminDeBase . $originalFileName;

            // On copie le fichier pour les autres ITVs
            for ($i = 1; $i < count($dto->itvCoches); $i++) {
                $itv = $dto->itvCoches[$i];
                $newFileName = $this->genererNomFichier($dto->numeroOr, $itv, $file);
                $destinationPath = $this->cheminDeBase . $newFileName;
                if (copy($sourcePath, $destinationPath)) {
                    $nomDesFichiers[] = $newFileName;
                } else {
                    $this->historiqueOperation->sendNotificationSoumission(
                        'Le fichier n\'a pas pu être copié pour l\'intervention : ' . $itv,
                        $newFileName,
                        'dit_liste'
                    );
                }
            }
        } catch (\Exception $e) {
            $this->historiqueOperation->sendNotificationSoumission(
                'Erreur lors du traitement du fichier pour l\'intervention : ' . $firstItv,
                $originalFileName ?: '-',
                'dit_liste'
            );
        }

        return $nomDesFichiers;
    }

    private function validerFichier(UploadedFile $file): bool
    {
        // Validation de la taille (5MB max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $this->historiqueOperation->sendNotificationSoumission(
                'Le fichier est trop volumineux (max 5MB)',
                $file->getClientOriginalName(),
                'dit_liste'
            );
            return false;
        }

        // Validation du type MIME
        $typesAutorises = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($file->getMimeType(), $typesAutorises)) {
            $this->historiqueOperation->sendNotificationSoumission(
                'Type de fichier non autorisé',
                $file->getClientOriginalName(),
                'dit_liste'
            );
            return false;
        }

        return true;
    }

    private function genererNomFichier(string $numeroOR, int $itv, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return sprintf('RI_%s-%d.%s', $numeroOR, $itv, $extension);
    }
}
