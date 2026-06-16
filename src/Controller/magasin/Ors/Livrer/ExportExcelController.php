<?php

namespace App\Controller\magasin\Ors\Livrer;

use App\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/or")
 */
class ExportExcelController extends Controller
{

    /**
     * @Route("/magasin-list-or-livrer-export-excel", name="export_liste_or_livrer")
     */
    public function exportExcel() {}
}
