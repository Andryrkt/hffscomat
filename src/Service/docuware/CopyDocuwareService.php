<?php

namespace App\Service\docuware;

class CopyDocuwareService
{
    public function copyCsvToDw($fileName, $filePath)
    {
        // $cheminFichierDepart = 'C:/Docuware/OR/' . $fileName;
        $cheminFichierDepart = 'ftp://ftp.docuware-online.de/VhhlMDUEYTbzBI_A8C6lpRt86g-wKO2lXFKfXfSP/data/' . $fileName;
        $cheminDestination = $filePath;

        copy($cheminDestination, $cheminFichierDepart);
    }
}
