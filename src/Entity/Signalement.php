<?php

namespace App\Entity;

use App\Enum\SignalementMotif;
use App\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
class Signalement
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 64, enumType: SignalementMotif::class)]
    #[Assert\NotNull]
    private ?SignalementMotif $motif = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private ?string $details = null;

    #[ORM\ManyToOne(inversedBy: 'signalements')]
    #[ORM\JoinColumn(name: 'stop_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BusStop $stop = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $incidentDate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(options: ['default' => false])]
    private bool $isTest = false;

    #[ORM\Column(length: 32, enumType: SignalementStatus::class, options: ['default' => 'en_cours'])]
    private SignalementStatus $status = SignalementStatus::EnCours;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->id = self::generateUuidV4();
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotif(): ?SignalementMotif
    {
        return $this->motif;
    }

    public function setMotif(SignalementMotif $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getStop(): ?BusStop
    {
        return $this->stop;
    }

    public function setStop(?BusStop $stop): static
    {
        $this->stop = $stop;

        return $this;
    }

    public function getIncidentDate(): ?\DateTimeImmutable
    {
        return $this->incidentDate;
    }

    public function setIncidentDate(?\DateTimeImmutable $incidentDate): static
    {
        $this->incidentDate = $incidentDate;

        return $this;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function isTest(): bool
    {
        return $this->isTest;
    }

    public function setIsTest(bool $isTest): static
    {
        $this->isTest = $isTest;

        return $this;
    }

    public function getStatus(): SignalementStatus
    {
        return $this->status;
    }

    public function setStatus(SignalementStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
