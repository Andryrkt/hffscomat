<?php

namespace App\Service\docuware;

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

        if (!@copy($cheminDestination, $cheminFichierDepart)) {
            $erreur = error_get_last();
            error_log("Échec de la copie du CSV $cheminDestination vers le FTP DocuWare ($fileName) : " . ($erreur['message'] ?? 'erreur inconnue'));
            return false;
        }

        return true;
    }
}
