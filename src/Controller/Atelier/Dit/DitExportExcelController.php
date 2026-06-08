<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitExportExcelController extends Controller
{
    /**
     * @Route("/export-excel", name="export_excel")
     */
    public function exportExcel() {}
}
