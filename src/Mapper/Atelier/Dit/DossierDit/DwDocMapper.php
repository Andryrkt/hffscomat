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
        $dto->dateCreation     = $item['date_creation'] ? (new \DateTime($item['date_creation']))->format('d/m/Y') : '-';
        $dto->dateModification = $item['date_derniere_modification'] ? (new \DateTime($item['date_derniere_modification']))->format('d/m/Y') : '-';
        $dto->numeroVersion    = $item['numero_version'] ?? '-';
        $dto->totalPage        = $item['total_page'] ?? '-';
        $dto->tailleFichier    = $this->convertFileSize((int) $item['taille_fichier']);
        $dto->extension        = $item['extension_fichier'] ?? '-';
        $dto->chemin           = $item['chemin'] ?? '-';

        return $dto;
    }

    private function getIconRaw(string $extension): Markup
    {
        $extension = strtolower($extension);
        $icon = self::ICONS[$extension] ?? '';

        return new Markup("<i class='fas fa-file$icon fs-4'></i>", 'UTF-8');
    }
}
