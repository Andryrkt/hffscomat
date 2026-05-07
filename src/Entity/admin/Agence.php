<?php

namespace App\Entity\admin;


use App\Entity\dom\Dom;
use App\Entity\badm\Badm;
use App\Entity\cas\Casier;
use App\Entity\admin\Service;
use App\Entity\da\DemandeAppro;
use App\Entity\Traits\DateTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\cas\CasierValider;
use App\Entity\mutation\Mutation;
use App\Entity\admin\AgenceService;
use App\Entity\admin\utilisateur\User;
use App\Entity\dit\DemandeIntervention;
use App\Repository\admin\AgenceRepository;
use Doctrine\Common\Collections\Collection;
use App\Entity\tik\DemandeSupportInformatique;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="agences")
 * @ORM\Entity(repositoryClass=AgenceRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Agence
{
    use DateTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="code_agence")
     *
     * @var string
     */
    private string  $codeAgence;

    /**
     * @ORM\Column(type="string", name="libelle_agence")
     *
     * @var string
     */
    private string $libelleAgence;

    /**
     * @ORM\Column(type="string", name="code_societe")
     *
     * @var string
     */
    private string $codeSociete;

    /**
     * @ORM\ManyToOne(targetEntity=Societte::class, inversedBy="agences")
     * @ORM\JoinColumn(name="societe_id", referencedColumnName="id")
     */
    private ?Societte $societe;

    /**
     * @ORM\OneToMany(targetEntity=AgenceService::class, mappedBy="agence", cascade={"persist", "remove"})
     */
    private Collection $agenceServices;



    public function __construct()
    {
        $this->agenceServices = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }


    public function getCodeAgence()
    {
        return $this->codeAgence;
    }

    public function setCodeAgence($codeAgence): self
    {
        $this->codeAgence = $codeAgence;

        return $this;
    }


    public function getLibelleAgence()
    {
        return $this->libelleAgence;
    }

    public function setLibelleAgence(string $libelleAgence): self
    {
        $this->libelleAgence = $libelleAgence;

        return $this;
    }

    public function getServices(): Collection
    {
        return $this->agenceServices->map(fn(AgenceService $as) => $as->getService());
    }

    /**
     * @return Collection<int, AgenceService>
     */
    public function getAgenceServices(): Collection
    {
        return $this->agenceServices;
    }

    public function addAgenceService(AgenceService $agenceService): self
    {
        $this->agenceServices[] = $agenceService;

        return $this;
    }

    public function removeAgenceService(AgenceService $agenceService): self
    {
        $this->agenceServices->removeElement($agenceService);

        return $this;
    }


    /**
     * Get the value of codeSociete
     */
    public function getCodeSociete(): string
    {
        return $this->codeSociete;
    }

    /**
     * Set the value of codeSociete
     */
    public function setCodeSociete(string $codeSociete): self
    {
        $this->codeSociete = $codeSociete;

        return $this;
    }

    /**
     * Get the value of societe
     */
    public function getSociete(): ?Societte
    {
        return $this->societe;
    }

    /**
     * Set the value of societe
     */
    public function setSociete(?Societte $societe): self
    {
        $this->societe = $societe;

        return $this;
    }
}
