<?php

namespace App\Mapper\Atelier\Dit\DossierDit;

use App\Dto\Atelier\Dit\DossierDit\DwDocDto;
use App\Traits\ConversionFileSizeTrait;
use Twig\Markup;

class DwDocMapper
{
    use ConversionFileSizeTrait;
    const ICONS = ['.pdf'  => '-pdf', '.doc'  => '-word', '.docx' => '-word', '.xls'  => '-excel', '.xlsx' => '-excel', '.jpg'  => '-image', '.jpeg' => '-image', '.png'  => '-image', '.zip'  => '-archive', '.rar'  => '-archive', '.txt'  => '-alt'];

    public function mapToDto(array $item): DwDocDto
    {
        $dto = new DwDocDto();


        $dto->iconRaw          = $this->getIconRaw($item['extension_fichier']);
        $dto->nomDoc           = $item['nom_doc'] ?? '-';
        $dto->numeroDoc        = $item['numero_doc'] ?? '-';
        $dto->dateCreation     = $this->convertToLocalTime($item['date_creation'], $item['heure_creation']);
        $dto->dateModification = $this->convertToLocalTime($item['date_derniere_modification'], $item['heure_derniere_modification']);

        $dto->numeroVersion    = $item['numero_version'] ?? '-';
        $dto->totalPage        = $item['total_page'] ?? '-';
        $dto->tailleFichier    = $this->convertFileSize((int) $item['taille_fichier']);
        $dto->extension        = $item['extension_fichier'] ?? '-';
        $dto->chemin           = $item['chemin'] ?? '-';

        $numOr = $item['numero_doc'] ?? '';
        if (!empty($numOr)) {
            $cheminFichier = ($_ENV['BASE_PATH_FICHIER'] ?? '') . '/dit/dev/fichiers/marge_ref_' . $numOr . '.xlsx';
            if (file_exists($cheminFichier)) {
                $dto->lienMargeRef = ($_ENV['BASE_PATH_FICHIER_COURT'] ?? '') . '/dit/dev/fichiers/marge_ref_' . $numOr . '.xlsx';
            }
        }

        return $dto;
    }

    private function getIconRaw(string $extension): Markup
    {
        $extension = strtolower($extension);
        $icon = self::ICONS[$extension] ?? '';

        return new Markup("<i class='fas fa-file$icon fs-4'></i>", 'UTF-8');
    }

    /**
     * Convertit une date/heure depuis un fuseau source vers UTC.
     *
     * @param string|null $date          Partie date (Y-m-d)
     * @param string|null $time          Partie heure (H:i:s) – optionnel, défaut 00:00:00
     * @param string      $sourceTz      Fuseau source (défaut : 'Indian/Antananarivo')
     * @param string      $outputFormat  Format de sortie (défaut : 'd/m/Y H:i:s')
     *
     * @return string Date/heure en UTC formatée, ou '-' en cas d'erreur
     */
    private function convertToLocalTime(
        ?string $date,
        ?string $time,
        string $sourceTz = 'UTC',
        string $targetTz = '+04:00',
        string $outputFormat = 'd/m/Y H:i'
    ): string {
        if (empty($date)) {
            return '-';
        }
        try {
            $time = $time ?? '00:00:00';
            $datetime = new \DateTime($date . ' ' . $time, new \DateTimeZone($sourceTz));
            $datetime->setTimezone(new \DateTimeZone($targetTz));
            return $datetime->format($outputFormat);
        } catch (\Exception $e) {
            return '-';
        }
    }
}
