<?php

namespace App\Controller\magasin\Ors\Traiter;

use App\Controller\Controller;

class OrTraiterController extends Controller
{
    public function index()
    {
        $data = [];
        
        return $this->render('magasin/ors/listOrATraiter.html.twig', [
            'data' => $data
        ]);
    }
}
