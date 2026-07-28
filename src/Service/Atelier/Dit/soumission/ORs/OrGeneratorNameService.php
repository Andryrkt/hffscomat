<?php


namespace App\Service\atelier\dit\soumission\ORs;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\fichier\AbstractFileNameGeneratorService;

class OrGeneratorNameService extends AbstractFileNameGeneratorService
{

    /**
     * Génère un nom pour les fichiers
     */
    public function generateNameFile(
        UploadedFile $file,
        string $numOr,
        int $numeroVersion,
        string $suffix,
        string $langueIps,
        int $index = 1
    ): string {
        return $this->generateFileName($file, [
            'format' => 'oRValidation{langueIps}_{numOr}-{numeroVersion}#{suffix}.{extension}',
            'variables' => [
                'langueIps' => $langueIps === 'A' ? 'EN' : 'FR',
                'numOr' => $numOr,
                'numeroVersion' => $numeroVersion,
                'suffix' => $suffix
            ],
            'sauter_premier_index' => false // Ne pas sauter le premier index
        ], $index);
    }
    /**
     * Gerer un nom pour la page de garde et le fichier fusionner
     */
    public function generateNamePrincipal(
        string $numOr,
        int $numeroVersion,
        string $suffix,
        string $langueIps
    ) {
        $langue =  $langueIps === 'A' ? 'EN' : 'FR';
        return "oRValidation{$langue}_$numOr-$numeroVersion#$suffix.pdf";
    }
}
