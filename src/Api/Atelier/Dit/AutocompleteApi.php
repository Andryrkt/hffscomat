<?php

namespace App\Api\Atelier\Dit;

use App\Controller\Controller;
use App\Model\Atelier\Dit\DitModel;
use Symfony\Component\Routing\Annotation\Route;

class AutocompleteApi extends Controller
{

    /**
     * @Route("/api/autocomplete/all-client", name="api_autocomplete_all_client")
     */
    public function autocompleteAllClient()
    {
        $results = [];

        $ditModel = new DitModel();
        // Recherchez les clients par nom dans votre base de données
        $clients = $ditModel->getAllClients();

        $results = array_map(function ($client) {
            return [
                'num_client' => $client['cbse_numcli'],
                'nom_client' => $client['cbse_nomcli'],
            ];
        }, $clients);

        header("Content-type:application/json");

        echo json_encode($results);
    }
}
