<?php

namespace App\Service\docuware;

use App\Service\historiqueOperation\suivi\SuiviOperationService;

class CopyDocuwareService
{
    /** 
     * Méthode pour copier le fichier csv des DIT clôturé vers le ftp de Docuware
     * 
     * @param string $fileName
     * @param string $cheminDestination
     * 
     * @return bool true si la copie a réussi
     */
    public function copyCsvToDw(string $fileName, string $cheminDestination): bool
    {
        $ftpDocuware = $_ENV['FTP_DOCUWARE'];
        $cheminFichierDepart = "ftp://$ftpDocuware/data/$fileName";

        // identifiants FTP retirés avant d'écrire l'adresse dans le suivi des opérations
        $destinationSansIdentifiants = preg_replace('#//[^/@]*@#', '//', $cheminFichierDepart);

        if (!@copy($cheminDestination, $cheminFichierDepart)) {
            $erreur = error_get_last()['message'] ?? 'erreur inconnue';
            $erreur = str_replace($cheminFichierDepart, $destinationSansIdentifiants, $erreur);
            SuiviOperationService::copieDocuware($cheminDestination, $destinationSansIdentifiants, false, 1, $erreur);
            error_log("Échec de la copie du CSV $cheminDestination vers le FTP DocuWare ($fileName) : $erreur");
            return false;
        }

        SuiviOperationService::copieDocuware($cheminDestination, $destinationSansIdentifiants, true, 1);
        return true;
    }
}
