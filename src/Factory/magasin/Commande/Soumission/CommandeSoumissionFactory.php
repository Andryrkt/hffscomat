<?php

namespace App\Factory\magasin\Commande\Soumission;

use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionLigneDTO;

class CommandeSoumissionFactory
{

    /**
     * @param array<int,array{num_cde:string,date_cde:string,type_cde:string,num_frn:string,nom_frn:string,agence_lib:string,service_lib:string,cst:string,refp:string,desi:string,qte_cde:string,package_qty:string,prix_unit:string,montant:string,poids_total:string,av_bt:string,fms:string,vte_der_mois:string,nbr_vente:string,stock_dispo:string,stock_min:string,stock_max:string,npr:string}> $data
     * @param string $email
     * 
     * @return CommandeSoumissionDTO|null
     */
    public function hydrate(array $data, string $email): ?CommandeSoumissionDTO
    {
        if (empty($data)) return null;

        $dto = new CommandeSoumissionDTO;

        $headerInfo = $data[0];

        $dto->dateCde         = new \DateTime($headerInfo['date_cde']);
        $dto->numeroCommande  = $headerInfo['num_cde'];
        $dto->typeCde         = $headerInfo['type_cde'];
        $dto->delaiExpedition = 0; // TODO: à spécifier plus tard
        $dto->numFrn          = $headerInfo['num_frn'];
        $dto->nomFrn          = $headerInfo['nom_frn'];
        $dto->responsable     = $email;
        $dto->libelleAgence   = $headerInfo['agence_lib'];
        $dto->libelleService  = $headerInfo['service_lib'];
        $dto->lignes          = $this->hydrateLignes($data);

        return $dto;
    }

    /**
     * @param array<int,array{num_cde:string,date_cde:string,type_cde:string,num_frn:string,nom_frn:string,agence_lib:string,service_lib:string,cst:string,refp:string,desi:string,qte_cde:string,package_qty:string,prix_unit:string,montant:string,poids_total:string,av_bt:string,fms:string,vte_der_mois:string,nbr_vente:string,stock_dispo:string,stock_min:string,stock_max:string,npr:string}> $data
     * 
     * @return list<CommandeSoumissionLigneDTO>
     */
    public function hydrateLignes(array $data): array
    {
        $lignes = [];

        foreach ($data as $key => $ligne) {
            $dtoLigne = new CommandeSoumissionLigneDTO;

            $dtoLigne->numLine        = $key + 1;
            $dtoLigne->const          = $ligne['cst'];
            $dtoLigne->avBat          = $ligne['av_bt'];
            $dtoLigne->ref            = $ligne['refp'];
            $dtoLigne->packQty        = $ligne['package_qty'];
            $dtoLigne->designation    = $ligne['desi'];
            $dtoLigne->npr            = $ligne['npr'];
            $dtoLigne->fms            = $ligne['fms'];
            $dtoLigne->ret            = ""; // TODO à spécifier plus tard
            $dtoLigne->qteDem         = (int) $ligne['qte_cde'];
            $dtoLigne->qteDispo       = (int) $ligne['stock_dispo'];
            $dtoLigne->qteDispoMin    = (int) $ligne['stock_min'];
            $dtoLigne->qteDispoMax    = (int) $ligne['stock_max'];
            $dtoLigne->qteVteDer6Mois = (int) $ligne['vte_der_mois'];
            $dtoLigne->nbrVteDer6Mois = (int) $ligne['nbr_vente'];
            $dtoLigne->prixUnitaire   = (float) $ligne['prix_unit'];
            $dtoLigne->prixTotal      = (float) $ligne['montant'];
            $dtoLigne->poids          = (float) $ligne['poids_total'];

            $lignes[] = $dtoLigne;
        }

        return $lignes;
    }
}
