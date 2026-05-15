<?php

namespace App\Service\magasin\devis\Fichier;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\fichier\AbstractFileNameGeneratorService;

class DevisMagasinGenererNameFileService extends AbstractFileNameGeneratorService
{
    /**
     * Génère un nom pour le vérification de prix
     */
    public function generateVerificationPrixName(
        UploadedFile $file,
        string $numDevis,
        int $numeroVersion,
        string $suffix,
        string $mail,
        int $index = 1
    ): string {
        return $this->generateFileName($file, [
            'format' => 'negverificationprix_{numDevis}-{numeroVersion}#{suffix}!{mail}.{extension}',
            'variables' => [
                'numDevis' => $numDevis,
                'numeroVersion' => $numeroVersion,
                'suffix' => $suffix,
                'mail' => $mail
            ],
            'sauter_premier_index' => false // Ne pas sauter le premier index
        ], $index);
    }

    /**
     * Génère un nom pour le cas de fichier excel
     */
    public function generateFichierExcelName(string $numDevis, string $extension): string
    {
        return "feuille_calcul_$numDevis.$extension";
    }

    /**
     * Génère un nom pour le bon de commande
     */
    public function generateBonCommandeName(
        UploadedFile $file,
        string $numDevis,
        int $numeroVersion,
        int $index = 1
    ): string {
        return $this->generateFileName($file, [
            'format' => 'SCbon_commande_{numDevis}-{numeroVersion}.{extension}',
            'variables' => [
                'numDevis' => $numDevis,
                'numeroVersion' => $numeroVersion,
            ],
            'sauter_premier_index' => false
        ], $index);
    }

    /**
     * Génère un nom pour le page de garde du bon de commande
     */
    public function generatePageGardeBonCommandeName(
        string $numDevis,
        int $numeroVersion
    ): string {
        return "SCbon_commande_{$numDevis}-{$numeroVersion}.pdf";
    }
}
