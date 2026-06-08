<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitDuplicationController extends Controller
{

    /**
     * @Route("/dit-duplication/{numDit}", name="dit_duplication")
     */
    public function Duplication($numDit, Request $request) {}
}
