<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitClotureController extends Controller
{
    /**
     * @Route("/cloturer-annuler/{numDit}", name="api_cloturer_annuler_dit_liste")
     */
    public function clotureStatut($numDit) {}
}
