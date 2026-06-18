<?php

namespace App\Dto\Atelier\Dit\DossierDit;

use Twig\Markup;

class DwDocDto
{
    public Markup $iconRaw;
    public string $nomDoc;
    public string $numeroDoc;
    public string $dateCreation;
    public string $dateModification;
    public string $numeroVersion;
    public string $totalPage;
    public string $tailleFichier;
    public string $extension;
    public string $chemin;
}
