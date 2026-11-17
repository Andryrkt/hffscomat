<?php
// src/Service/atelier/dit/DitCreationService.php

namespace App\Service\Atelier\Dit;

use App\Dto\Atelier\Dit\DitDto;
use App\Factory\Atelier\Dit\DitFactory;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Atelier\Dit\DitModel;
use App\Service\atelier\dit\TraitementFichierService;
use App\Service\genererPdf\dit\GenererPdfDit;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\suivi\SuiviOperationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class DitCreationService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var DitFactory */
    private $ditFactory;

    /** @var DitModel */
    private $ditModel;

    public function __construct(
        EntityManagerInterface $em,
        DitFactory $ditFactory,
        DitModel $ditModel
    ) {
        $this->em         = $em;
        $this->ditFactory = $ditFactory;
        $this->ditModel   = $ditModel;
    }

    /**
     * Cas 1 : création depuis le formulaire (avec fichiers + avecMateriel)
     */
    public function creerDepuisFormulaire(FormInterface $form, DitDto $dto, bool $avecMateriel): array
    {
        $dto = $this->ditFactory->apresSoumission($dto, $avecMateriel);

        $fichiers = (new TraitementFichierService())->traitementDeFichier($form, $dto);
        [$nomFichierEnregistrer, $nomFichier] = $fichiers;

        return $this->executerCreation($dto, $nomFichierEnregistrer, $nomFichier);
    }

    /**
     * Cas 2 : création depuis un OR
     */
    public function creerDepuisOr(array $orData, string $codeSociete, array $agenceService): array
    {
        $dto = $this->ditFactory->initialisation($agenceService, $codeSociete);

        // ---- MAPPING OR → DIT (à compléter selon tes colonnes réelles) ----
        $dto->numeroOr          = $orData['numero_or']           ?? null;
        $dto->statutOr          = $orData['statut_or']           ?? null;
        $dto->numeroClient      = $orData['numero_client']       ?? null;
        $dto->nomClient         = $orData['nom_client']          ?? null;
        $dto->mailClient        = $orData['mail_client']         ?? null;
        $dto->numeroTel         = $orData['numero_telephone']    ?? null;
        $dto->objetDemande      = $orData['objet_demande']       ?? null;
        $dto->detailDemande     = $orData['detail_demande']      ?? null;
        $dto->typeDocument      = $orData['type_document']       ?? null;
        $dto->categorieDemande  = $orData['categorie_demande']   ?? null;
        $dto->typeReparation    = $orData['type_reparation']     ?? null;
        $dto->demandeDevis      = $orData['demande_devis']       ?? null;
        $dto->clientSousContrat = $orData['client_sous_contrat'] ?? null;
        $dto->avisRecouvrement  = $orData['avis_recouvrement']   ?? null;
        $dto->internetExterne   = $orData['internet_externe']    ?? null;

        $dto->dateSoumissionOr = !empty($orData['date_or'])
            ? new \DateTime($orData['date_or']) : null;
        $dto->datePrevueTravaux = !empty($orData['date_prevue_travaux'])
            ? new \DateTime($orData['date_prevue_travaux']) : null;

        $dto->idMateriel = $orData['id_materiel']  ?? null;
        $dto->numParc    = $orData['numero_parc']  ?? null;
        $dto->numSerie   = $orData['numero_serie'] ?? null;
        $dto->km         = $orData['km']           ?? null;
        $dto->heure      = $orData['heure']        ?? null;

        $avecMateriel = !empty($dto->idMateriel)
            || !empty($dto->numParc)
            || !empty($dto->numSerie);

        $dto = $this->ditFactory->apresSoumission($dto, $avecMateriel);

        return $this->executerCreation($dto, [null, null, null], null);
    }

    /**
     * Pipeline commun (DB → DocuWare → mise à jour DW)
     */
    private function executerCreation(DitDto $dto, $nomFichierEnregistrer, $nomFichier): array
    {
        $numero  = $dto->numeroDemandeIntervention;
        $suivi   = SuiviOperationService::demarrer(SuiviOperationService::TYPE_DIT, (string) $numero);
        $message = 'Votre demande a été enregistrée';
        $copieOk = false;

        try {
            $self = $this;

            $suivi->etape('Enregistrement base de données', function () use ($self, $dto, $nomFichierEnregistrer) {
                $data = DitMapper::DtoToArray($dto, $self->getEntityManager(), $nomFichierEnregistrer);
                $self->getDitModel()->enregistrementDit($data);
            });

            $genererPdfDit = new GenererPdfDit();
            try {
                $copieOk = $suivi->etape('Copie DocuWare', function () use ($genererPdfDit, $nomFichier, $numero) {
                    $genererPdfDit->exigerCopieReussie(
                        $genererPdfDit->copyToDOCUWARE($nomFichier, $numero)
                    );
                    return true;
                });
            } catch (EtapeOperationException $e) {
                $copieOk  = false;
                $message .= ", mais la copie dans DocuWare a échoué";
            }

            $suivi->etape('Mise à jour dépôt DocuWare', function () use ($self, $copieOk, $dto) {
                $donnees = DitMapper::updateDit($copieOk);
                $self->getDitModel()->updateDitDW($donnees, $dto);
            });

            return ['success' => true, 'message' => $message, 'numero' => $numero];
        } catch (EtapeOperationException $e) {
            return [
                'success' => false,
                'message' => sprintf(
                    "Echec de l'enregistrement de la DIT %s à l'étape « %s ». Veuillez contacter l'administrateur.",
                    $numero,
                    $e->getEtape()
                ),
                'numero' => $numero,
            ];
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getDitModel(): DitModel
    {
        return $this->ditModel;
    }
}
