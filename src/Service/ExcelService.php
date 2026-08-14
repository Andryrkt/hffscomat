<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelService
{
    public function createSpreadsheet(array $data, $filename = "donnees")
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ajouter des données
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        // $response = new StreamedResponse(function() use ($spreadsheet) {
        //     $writer = new Xlsx($spreadsheet);
        //     $writer->save('php://output');
        // });

        // $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // $response->headers->set('Content-Disposition', 'attachment;filename="export.xlsx"');
        // $response->headers->set('Cache-Control', 'max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        setcookie('fileDownload', 'true', 0, '/');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function createSpreadsheetEnregistrer(array $data, string $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ajouter des données
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        // Définir le chemin et nom du fichier à enregistrer
        // $filename = 'donnees_' . date('Ymd_His') . '.xlsx';
        // $filePath = __DIR__ . '/exports/' . $filename; // Assure-toi que le dossier 'exports/' existe et est accessible en écriture

        // Créer le dossier si nécessaire
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // Sauvegarder le fichier sur le disque
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath; // Tu peux retourner le chemin pour le réutiliser (par ex. pour un lien de téléchargement)
    }

    public function createSpreadsheetMode(array $data, $startCell = 'A1')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Initialiser l'index de ligne et de colonne
        [$startColumn, $startRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($startCell);
        $startColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startColumn);

        // Ajouter des données en partant de la cellule spécifique
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($startColumnIndex + $colIndex, $startRow + $rowIndex, $value);
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="donnees.xlsx"');
        setcookie('fileDownload', 'true', 0, '/');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * Génère un fichier Excel pour le tableau de marge par référence contenant les 3 tableaux (CAT, MFN, AUTRES).
     *
     * @param array $tableauMargeReference Tableau contenant 'tableauMargeCat', 'tableauMargeMfn', 'tableauMargeAutres'
     * @param string|null $filePath Chemin où enregistrer le fichier (si null, le fichier est envoyé en téléchargement HTTP)
     * @param string $filename Nom du fichier en cas de téléchargement HTTP
     * @return string|void
     */
    public function genererExcelTableauMargeReference(array $tableauMargeReference, ?string $filePath = null, string $filename = "tableau_marge_reference")
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Marge Reference');

        $sections = [
            'tableauMargeCat'    => 'CAT',
            'tableauMargeMfn'    => 'MFN',
            'tableauMargeAutres' => 'AUTRES',
        ];

        $formatterPourcentage = function ($value) {
            return ($value == 0.0 || $value === null || $value === '') ? '-' : round((float) $value) . '%';
        };

        $formatterDispoStock = function ($row) {
            return (int) ($row['nb_ref'] ?? 0) === 0 ? 'Non dispo stock' : 'Dispo stock';
        };

        $headers = [
            'Ref',
            'Designation',
            'Famille',
            'Qte stock',
            'Qte dem',
            'PMP',
            'PV Brut',
            'Mt Remise',
            'PV Net remisé',
            'MB',
            '%MB'
        ];

        $currentRow = 1;

        foreach ($sections as $key => $label) {
            $lignes = $tableauMargeReference[$key] ?? [];

            // En-tête avec le label de la catégorie en première colonne
            $headerRow = array_merge([$label], $headers);

            foreach ($headerRow as $colIndex => $headerText) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $currentRow;
                $sheet->setCellValue($cellCoordinate, $headerText);
            }

            // Style de l'en-tête (Gras + Aligné au centre + couleur de fond)
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headerRow));
            $headerRange = "A{$currentRow}:{$lastColLetter}{$currentRow}";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('EAEAEA');

            $currentRow++;

            if (!empty($lignes)) {
                foreach ($lignes as $item) {
                    $dispoStock = $formatterDispoStock($item);
                    $ref        = $item['reference'] ?? '';
                    $designation = $item['designation'] ?? '';
                    $famille    = $item['famille'] ?? '';
                    $nbRef      = $item['nb_ref'] ?? 0;
                    $qteDem     = $item['quantite_demander'] ?? 0;
                    $pmp        = (isset($item['pmp']) && $item['pmp'] !== '') ? $item['pmp'] : '-';
                    $pvBrut     = (isset($item['pv_brut']) && $item['pv_brut'] !== '') ? $item['pv_brut'] : '-';
                    $mtRemise   = (isset($item['mt_remise']) && $item['mt_remise'] !== '') ? $item['mt_remise'] : '-';
                    $pvNet      = (isset($item['pv_net_remise']) && $item['pv_net_remise'] !== '') ? $item['pv_net_remise'] : '-';
                    $mb         = (isset($item['mb']) && $item['mb'] !== '') ? $item['mb'] : '-';
                    $mbP        = isset($item['mb_p']) ? $formatterPourcentage($item['mb_p']) : '-';

                    $rowValues = [$dispoStock, $ref, $designation, $famille,  $nbRef, $qteDem, $pmp, $pvBrut, $mtRemise, $pvNet, $mb, $mbP];

                    foreach ($rowValues as $colIndex => $val) {
                        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $currentRow;
                        $sheet->setCellValue($cellCoordinate, $val);
                    }

                    // Alignements par cellule
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("E{$currentRow}:J{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    $currentRow++;
                }
            }

            // Espace d'une ligne entre les tableaux
            $currentRow += 1;
        }

        // Auto-fit des colonnes
        for ($col = 1; $col <= 10; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        if ($filePath !== null) {
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0777, true);
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);
            return $filePath;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        setcookie('fileDownload', 'true', 0, '/');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
