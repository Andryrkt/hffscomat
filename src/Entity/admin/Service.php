<?php

namespace App\Entity\admin;


use App\Entity\dom\Dom;
use App\Entity\badm\Badm;
use App\Entity\admin\Agence;
use App\Entity\Traits\DateTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\mutation\Mutation;
use App\Entity\admin\AgenceService;
use App\Entity\dit\DemandeIntervention;
use App\Repository\admin\ServiceRepository;
use Doctrine\Common\Collections\Collection;
use App\Entity\tik\DemandeSupportInformatique;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="services")
 * @ORM\Entity(repositoryClass=ServiceRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Service
{
    use DateTrait;

    public const ID_ATELIER = 3;
    public const ID_APPRO = 16;
    public const ID_RH = 12;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column("string", name="code_service")
     *
     * @var string
     */
    private string $codeService;

    /**
     * @ORM\Column("string", name="libelle_service")
     *
     * @var string
     */
    private string $libelleService;

    /**
     * @ORM\OneToMany(targetEntity=AgenceService::class, mappedBy="service", cascade={"persist", "remove"})
     */
    private Collection $agenceServices;



    /**=====================================================================================
     * 
     * GETTERS and SETTERS
     *
    =====================================================================================*/

    public function __construct()
    {
        $this->agenceServices = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }



    public function getCodeService()
    {
        return $this->codeService;
    }


    public function setCodeService($codeService): self
    {
        $this->codeService = $codeService;

        return $this;
    }


    public function getLibelleService()
    {
        return $this->libelleService;
    }


    public function setLibelleService(string $libelleService): self
    {
        $this->libelleService = $libelleService;

        return $this;
    }


    public function getAgences(): Collection
    {
        return $this->agenceServices->map(fn(AgenceService $as) => $as->getAgence());
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
}
