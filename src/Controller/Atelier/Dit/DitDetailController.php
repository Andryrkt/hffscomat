<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitDetailController extends Controller
{
    /**
     * @Route("/detail/{numDit<\w+>}", name="dit_detail")
     */
    public function validationDit($numDit, Request $request) {}
}
