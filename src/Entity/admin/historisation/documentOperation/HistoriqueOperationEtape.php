<?php

namespace App\Entity\admin\historisation\documentOperation;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\admin\historisation\documentOperation\HistoriqueOperationEtapeRepository;

/**
 * Détail d'une étape (upload, fusion, BD, DocuWare, mail...) d'une opération historisée
 *
 * @ORM\Entity(repositoryClass=HistoriqueOperationEtapeRepository::class)
 * @ORM\Table(name="historique_operation_etape")
 */
class HistoriqueOperationEtape
{
    public const STATUT_SUCCES = 'Succès';
    public const STATUT_ECHEC  = 'Echec';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=HistoriqueOperationDocument::class)
     * @ORM\JoinColumn(name="idHistoriqueOperation", referencedColumnName="id", nullable=false)
     */
    private HistoriqueOperationDocument $historiqueOperation;

    /**
     * @ORM\Column(type="integer")
     */
    private int $ordre;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $etape;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $statut;

    /**
     * @ORM\Column(type="integer", name="duree_ms", nullable=true)
     */
    private ?int $dureeMs = null;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private ?string $message = null;

    /**
     * @ORM\Column(type="datetime", name="date_etape")
     */
    private \DateTime $dateEtape;

    public function __construct()
    {
        $this->dateEtape = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getHistoriqueOperation(): HistoriqueOperationDocument
    {
        return $this->historiqueOperation;
    }

    public function setHistoriqueOperation(HistoriqueOperationDocument $historiqueOperation): self
    {
        $this->historiqueOperation = $historiqueOperation;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getEtape(): string
    {
        return $this->etape;
    }

    public function setEtape(string $etape): self
    {
        $this->etape = mb_substr($etape, 0, 100);

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDureeMs(): ?int
    {
        return $this->dureeMs;
    }

    public function setDureeMs(?int $dureeMs): self
    {
        $this->dureeMs = $dureeMs;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message === null ? null : mb_substr($message, 0, 1000);

        return $this;
    }

    public function getDateEtape(): \DateTime
    {
        return $this->dateEtape;
    }

    public function setDateEtape(\DateTime $dateEtape): self
    {
        $this->dateEtape = $dateEtape;

        return $this;
    }
}
