<?php
// src/Service/atelier/dit/DitCreationService.php

namespace App\Service\Atelier\Dit;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\Atelier\Dit\DitDto;
use App\Factory\Atelier\Dit\DitFactory;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
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
    public function creerDepuisOr(array $orData, string $codeSociete, array $agenceService, string $numeroDit): array
    {
        $now = new \DateTime();

        /** @var DitDto $dto */
        $dto = $this->ditFactory->initialisation($agenceService, $codeSociete);
        // ↑ initialisation() remplit déjà agenceEmetteur, serviceEmetteur,
        //   agence, service, worNiveauUrgence, codeSociete.

        // important
        $dto->sourceCreation = 'OR';

        // ---------- 1. Mapping direct ----------
        $dto->numeroOr     = $orData['numero_or']     ?? null;
        $dto->statutOr     = 'Validé';
        // $dto->codeSociete déjà rempli par initialisation() — mais on peut forcer :
        $dto->codeSociete  = $orData['code_societe']  ?? $codeSociete;
        $dto->numeroClient = $orData['numero_client'] ?? null;
        $dto->nomClient    = $orData['nom_client']    ?? null;

        $dto->idMateriel = isset($orData['id_materiel'])  ? (string) $orData['id_materiel']  : null;
        $dto->numParc  = isset($orData['numero_parc'])  && trim((string) $orData['numero_parc'])  !== ''
            ? trim((string) $orData['numero_parc'])
            : null;

        $dto->numSerie = isset($orData['numero_serie']) && trim((string) $orData['numero_serie']) !== ''
            ? trim((string) $orData['numero_serie'])
            : null;

        $dto->dateSoumissionOr  = $this->toDateString($orData['date_or']             ?? null);
        $dto->datePrevueTravaux = $this->toDateTime($orData['date_prevue_travaux'] ?? null);

        // ---------- 3. Valeurs fixes ----------
        $dto->internetExterne   = 'Externe';
        $dto->avisRecouvrement  = 'NON';
        $dto->clientSousContrat = 'OUI';
        $dto->livraisonPartiel  = 'NON';
        $dto->statutOr  = ConstantStatutOr::STATUT_VALIDE;
        $dto->id_statut_demande = 53;

        // ---------- 4. Champs calculés ----------
        $leadTime       = $this->calculerLeadTime($orData['date_prevue_travaux'] ?? null);
        $numeroMateriel = $dto->idMateriel ?? '';
        $dto->objetDemande  = trim(sprintf('CVI %s %s ', $numeroMateriel, $leadTime));
        $dto->detailDemande  = '...';

        // ---------- 5. Date / heure du jour ----------
        $dto->dateDemande      = $now->format('Y-m-d');
        $dto->heureDemande     = $now->format('H:i:s');
        $dto->dateValidationOr = $now->format('Y-m-d');

        // ---------- 6. À arbitrer ----------
        $dto->typeDocument      = 'Autres';
        $dto->typeReparation    = 'AUTRES';
        $dto->reparationRealise = 'WS SCOMAT';
        $dto->categorieDemande  = 'AUTRES';
        $dto->sectionAffectee   = null;
        $dto->mailDemandeur     = null;

        // ---------- 7. DocuWare ----------
        $dto->pdfDeposerDw   = 0;
        $dto->dateDepotPdfDw = null;

        // ---------- 8. Finalisation ----------
        $avecMateriel = !empty($dto->idMateriel);
        $dto = $this->ditFactory->apresSoumission($dto, $avecMateriel, $numeroDit);
        [$nomFichierEnregistrer, $nomFichier] = (new TraitementFichierService())
            ->genererPdfSansFichier($dto);
        return $this->executerCreation($dto, $nomFichierEnregistrer, $nomFichier);
    }

    /**
     * Pipeline commun (DB → DocuWare → mise à jour DW)
     */
    private function executerCreation(DitDto $dto, array $nomFichierEnregistrer, string $nomFichier): array
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

    private function toDateString($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        try {
            return (new \DateTime((string) $value))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toDateTime($value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return new \DateTime($value->format('Y-m-d'));
        }
        try {
            return new \DateTime((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calculerLeadTime($dateDelai): string
    {
        $dt = $this->toDateTime($dateDelai);
        if (!$dt) {
            return '';
        }
        $jours = (int) (new \DateTime('today'))->diff($dt)->format('%r%a');
        return $jours > 0 ? $jours . 'j' : '0j';
    }

    /**
     * Recupératin du niveau d'urence P2
     *
     * @return string|null
     */
    private function getP2NiveauUrgence(): ?string
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        return $worNiveauUrgenceModel->getP2Description();
    }
}
