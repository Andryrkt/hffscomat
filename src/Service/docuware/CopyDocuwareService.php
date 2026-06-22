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
     * @return void
     */
    public function copyCsvToDw(string $fileName, string $cheminDestination)
    {
        $ftpDocuware = $_ENV['FTP_DOCUWARE'];
        $cheminFichierDepart = "ftp://$ftpDocuware/data/$fileName";

        copy($cheminDestination, $cheminFichierDepart);
    }
}
