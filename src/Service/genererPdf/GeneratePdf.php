<?php

namespace App\Service\genererPdf;

use App\Service\historiqueOperation\suivi\SuiviOperationService;
use TCPDF;

class GeneratePdf
{
    protected string $baseCheminDuFichier;
    protected string $baseCheminDocuware;
    protected string $basePathAssets;

    public function __construct(
        ?string $baseCheminDuFichier = null,
        ?string $baseCheminDocuware = null
    ) {
        // Injection de dépendances avec fallback sur les variables d'environnement
        $this->baseCheminDuFichier = $baseCheminDuFichier ?? rtrim($_ENV['BASE_PATH_FICHIER'] ?? '', '/\\') . '/';
        $this->baseCheminDocuware = $baseCheminDocuware ?? rtrim($_ENV['BASE_PATH_DOCUWARE'] ?? '', '/\\') . '/';
        $this->basePathAssets = rtrim($_ENV['BASE_PATH_LONG'] ?? '', '/\\') . '/Views/assets';
    }

    // commande fournisseur magasin
    public static function copyToDWCdeFnrSoumis(string $cheminDuFichier, string $numCmde): bool
    {
        $self = new self();
        $cheminDW = rtrim($self->baseCheminDocuware, '/\\') . "/CDE FRN MAGASIN/$numCmde.pdf";
        return $self->copyFile($cheminDuFichier, $cheminDW);
    }

    /** Message de la dernière erreur de copie (null si la dernière copie a réussi) */
    protected ?string $derniereErreurCopie = null;

    public function getDerniereErreurCopie(): ?string
    {
        return $this->derniereErreurCopie;
    }

    /**
     * Lève une exception avec la raison de l'échec si la copie n'a pas réussi
     * (à utiliser dans une étape de SuiviOperationService)
     *
     * @throws \RuntimeException
     */
    public function exigerCopieReussie(bool $copieReussie): void
    {
        if (!$copieReussie) {
            throw new \RuntimeException($this->derniereErreurCopie ?? 'copie vers DocuWare échouée');
        }
    }

    protected function copyFile(string $sourcePath, string $destinationPath): bool
    {
        $this->derniereErreurCopie = null;

        // Fonction interne pour tenter la copie, renvoie null si OK sinon le message d'erreur
        $attemptCopy = function () use ($sourcePath, $destinationPath): ?string {
            try {
                if (!file_exists($sourcePath)) {
                    return "fichier source introuvable : $sourcePath";
                }

                $destinationDir = dirname($destinationPath);
                if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0777, true) && !is_dir($destinationDir)) {
                    return "impossible de créer le dossier de destination : $destinationDir";
                }

                if (!@copy($sourcePath, $destinationPath)) {
                    $erreur = error_get_last();
                    return "copie impossible vers $destinationPath" . ($erreur ? " (" . $erreur['message'] . ")" : "");
                }

                // Vérification : le fichier copié doit avoir la même taille que la source
                clearstatcache(true, $destinationPath);
                $tailleSource = filesize($sourcePath);
                $tailleDestination = file_exists($destinationPath) ? filesize($destinationPath) : false;
                if (!$tailleDestination || $tailleDestination !== $tailleSource) {
                    return "fichier copié incomplet vers $destinationPath (source : $tailleSource octets, destination : " . var_export($tailleDestination, true) . ")";
                }

                return null;
            } catch (\Throwable $e) {
                return $e->getMessage();
            }
        };

        // Première tentative
        if (($erreur = $attemptCopy()) === null) {
            SuiviOperationService::copieDocuware($sourcePath, $destinationPath, true, 1);
            return true;
        }

        // Deuxième tentative après un court délai
        usleep(50000); // 50ms
        if (($erreur = $attemptCopy()) === null) {
            SuiviOperationService::copieDocuware($sourcePath, $destinationPath, true, 2);
            return true;
        }

        $this->derniereErreurCopie = $erreur;
        SuiviOperationService::copieDocuware($sourcePath, $destinationPath, false, 2, $erreur);
        error_log("Échec de copyFile après 2 tentatives : $sourcePath -> $destinationPath : $erreur");
        return false;
    }




    /**
     * Méthode pour ajouter un titre au PDF
     * 
     * @param TCPDF $pdf le pdf à générer
     * @param string $title le titre du pdf
     * @param string $font le style de la police pour le titre
     * @param string $style le font-weight du titre
     * @param int $size le font-size du titre
     * @param string $align l'alignement
     * @param int $lineBreak le retour à la ligne
     */
    protected function addTitle(TCPDF $pdf, string $title, string $font = 'helvetica', string $style = 'B', int $size = 10, string $align = 'L', int $lineBreak = 5)
    {
        $pdf->setFont($font, $style, $size);

        // Calculer la largeur de la cellule en fonction de la page
        $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];

        // Utiliser MultiCell pour gérer les titres longs
        $pdf->MultiCell($pageWidth, 6, $title, 0, $align, false, 1, '', '', true);

        // Ajouter un espace après le titre
        $pdf->Ln($lineBreak, true);
    }

    /** 
     * Méthode pour ajouter des détails (sommaire) au PDF
     * 
     * @param TCPDF $pdf le pdf à générer
     * @param array $details tableau des détails à insérer dans le PDF
     * @param string $font le style de la police pour les détails
     * @param int $fontSize le font-size du détail 
     * @param int $labelWidth la largeur du label du tableau de détails
     * @param int $valueWidth la largeur du value du tableau de détails
     * @param int $lineHeight le retour à la ligne après chaque détail
     * @param int $spacingAfter le retour à la ligne après les détails
     */
    protected function addSummaryDetails(TCPDF $pdf, array $details, string $font = 'helvetica', int $fontSize = 10, int $labelWidth = 45, int $valueWidth = 50, int $lineHeight = 5, int $spacingAfter = 5)
    {
        $pdf->setFont($font, '', $fontSize);

        foreach ($details as $label => $value) {
            $pdf->Cell($labelWidth, 6, ' - ' . $label, 0, 0, 'L', false, '', 0, false, 'T', 'M');
            $pdf->Cell($valueWidth, 5, ': ' . $value, 0, 0, '', false, '', 0, false, 'T', 'M');
            $pdf->Ln($lineHeight, true);
        }

        $pdf->Ln($spacingAfter, true);
    }

    /** 
     * Méthode pour ajouter des détails (en gras) au PDF
     * 
     * @param TCPDF $pdf le pdf à générer
     * @param array $details tableau des détails à insérer dans le PDF
     * @param string $font le style de la police pour les détails
     * @param int $labelWidth la largeur du label du tableau de détails
     * @param int $valueWidth la largeur du value du tableau de détails
     * @param int $lineHeight le retour à la ligne après chaque détail
     * @param int $spacing espace
     * @param int $spacingAfter le retour à la ligne après le bloc de détails
     */
    protected function addDetailsBlock(TCPDF $pdf, array $details, string $font = 'helvetica', int $labelWidth = 45, int $valueWidth = 50, int $lineHeight = 6, int $spacing = 2, int $spacingAfter = 10)
    {
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        foreach ($details as $label => $value) {
            // Positionnement du label
            $pdf->SetXY($startX, $pdf->GetY() + $spacing);
            $pdf->setFont($font, 'B', 10);
            $pdf->Cell($labelWidth, $lineHeight, $label, 0, 0, 'L', false, '', 0, false, 'T', 'M');

            // Positionnement de la valeur
            $pdf->setFont($font, '', 10);
            $pdf->Cell($valueWidth, $lineHeight, ': ' . $value, 0, 1, '', false, '', 0, false, 'T', 'M');
        }

        // Ajout d'un espace après le bloc
        $pdf->Ln($spacingAfter, true);
    }

    /** 
     * Méthode pour générer une ligne de caractères (ligne de séparation)
     * 
     * @param TCPDF $pdf le pdf à générer
     * @param string $char le caractère pour faire la séparation
     * @param string $font le style de la police pour le caractère
     */
    protected function generateSeparateLine(TCPDF $pdf, string $char = '*', string $font = 'helvetica')
    {
        // Définir la largeur disponible
        $pageWidth = $pdf->GetPageWidth(); // Largeur totale de la page
        $leftMargin = $pdf->getOriginalMargins()['left']; // Marge gauche
        $rightMargin = $pdf->getOriginalMargins()['right']; // Marge droite
        $usableWidth = $pageWidth - $leftMargin - $rightMargin; // Largeur utilisable

        // Définir la police
        $pdf->SetFont($font, '', 12);

        $charWidth = $pdf->GetStringWidth($char); // Largeur d'un seul caractère
        $numChars = floor($usableWidth / $charWidth); // Nombre total de caractères pour remplir la largeur
        $line = str_repeat($char, $numChars); // Répéter le caractère

        // Afficher la ligne de séparation
        $pdf->Cell(0, 10, $line, 0, 1, 'C'); // Une cellule contenant la ligne
        //$pdf->Ln(5); // Ajouter un espacement en dessous de la ligne
    }

    protected function renderTextWithLine(
        TCPDF $pdf,
        string $text,
        int $totalWidth = 190,
        int $lineOffset = 3,
        string $font = 'helvetica',
        string $fontStyle = 'B',
        int $fontSize = 11,
        array $textColor = [14, 65, 148],
        array $lineColor = [14, 65, 148],
        int $lineHeight = 1
    ) {
        // Set font and text color
        $pdf->setFont($font, $fontStyle, $fontSize);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // Calculate text width
        $textWidth = $pdf->GetStringWidth($text);

        // Add the text
        $pdf->Cell($textWidth, 6, $text, 0, 0, 'L');

        // Set fill color for the line
        $pdf->SetFillColor($lineColor[0], $lineColor[1], $lineColor[2]);

        // Calculate the remaining width for the line
        $remainingWidth = $totalWidth - $textWidth - $lineOffset;

        // Calculate the position for the line (next to the text)
        $lineStartX = $pdf->GetX() + $lineOffset; // Add a small offset
        $lineStartY = $pdf->GetY() + 3; // Adjust for alignment

        // Draw the line
        if ($remainingWidth > 0) { // Only draw if there is space left for the line
            $pdf->Rect($lineStartX, $lineStartY, $remainingWidth, $lineHeight, 'F');
        }

        // Move to the next line
        $pdf->Ln(6, true);
    }
}
