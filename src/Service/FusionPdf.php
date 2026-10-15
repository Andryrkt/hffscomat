<?php

namespace App\Service;

use setasign\Fpdi\Tcpdf\Fpdi;



class FusionPdf
{

    /**
     * FONCTION GENERALE POUR FUSIONNER DES PDF
     *
     * @param array $files
     * @param [type] $outputFile
     * @return void
     */
    function mergePdfs(array $files, $outputFile)
    {
        // Instancier FPDI
        $pdf = new FPDI();

        // Désactiver les en-têtes et pieds de page automatiques
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Boucle sur chaque fichier PDF à fusionner
        foreach ($files as $file) {
            $this->verifierFichierSource($file);
            try {
                $pageCount = $pdf->setSourceFile($file);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $tplIdx = $pdf->importPage($pageNo);
                    $specs = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($specs['orientation'], [$specs['width'], $specs['height']]);
                    $pdf->useTemplate($tplIdx);
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException("Fusion impossible du fichier " . basename($file) . " : " . $e->getMessage(), 0, $e);
            }
        }

        // nombre de pages lu avant Output(), qui libère le document TCPDF
        $nombrePages = $pdf->getNumPages();

        // Enregistrer le fichier PDF fusionné
        $pdf->Output($outputFile, 'F');

        $this->verifierFichierFusionne($outputFile, $nombrePages);
    }

    public function mergePdfsAndImages($files, $outputFile)
    {
        // Créer une instance de FPDI pour le document final
        $pdf = new Fpdi();

        foreach ($files as $file) {
            $this->verifierFichierSource($file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if ($ext === 'pdf') {
                $pageCount = $pdf->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplId);
                }
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                // Récupérer les dimensions de l'image
                list($imgWidth, $imgHeight) = getimagesize($file);

                // Calculer l'orientation de la page en fonction des dimensions de l'image
                $orientation = ($imgWidth > $imgHeight) ? 'L' : 'P';

                // Ajouter une page avec l'orientation calculée
                $pdf->AddPage($orientation);

                // Récupérer les dimensions de la page
                $pageWidth = $pdf->GetPageWidth();
                $pageHeight = $pdf->GetPageHeight();

                // Calculer les dimensions pour centrer l'image
                $scale = min($pageWidth / $imgWidth, $pageHeight / $imgHeight);
                $imgDisplayWidth = $imgWidth * $scale;
                $imgDisplayHeight = $imgHeight * $scale;

                $x = ($pageWidth - $imgDisplayWidth) / 2;
                $y = ($pageHeight - $imgDisplayHeight) / 2;

                // Ajouter l'image centrée
                $pdf->Image($file, $x, $y, $imgDisplayWidth, $imgDisplayHeight);
            }
        }

        // nombre de pages lu avant Output(), qui libère le document TCPDF
        $nombrePages = $pdf->getNumPages();

        // Sauvegarder le fichier PDF fusionné
        $pdf->Output($outputFile, 'F');

        $this->verifierFichierFusionne($outputFile, $nombrePages);
    }

    private function verifierFichierSource(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException("Fichier à fusionner introuvable ou illisible : $file");
        }
        if (filesize($file) === 0) {
            throw new \RuntimeException("Fichier à fusionner vide : $file");
        }
    }

    /**
     * Vérifie que le fichier fusionné a bien été écrit et contient des pages
     */
    private function verifierFichierFusionne(string $outputFile, int $nombrePages): void
    {
        clearstatcache(true, $outputFile);

        if ($nombrePages < 1) {
            throw new \RuntimeException("Le PDF fusionné ne contient aucune page : $outputFile");
        }
        if (!is_file($outputFile) || filesize($outputFile) === 0) {
            throw new \RuntimeException("Le PDF fusionné n'a pas été écrit : $outputFile");
        }
        if (file_get_contents($outputFile, false, null, 0, 5) !== '%PDF-') {
            throw new \RuntimeException("Le fichier fusionné n'est pas un PDF valide : $outputFile");
        }
    }
}
