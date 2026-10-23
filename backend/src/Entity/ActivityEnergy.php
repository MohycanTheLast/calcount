<?php

namespace App\Entity;

use App\Entity\Security\User;
use App\Repository\ActivityEnergyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Aktivní kalorie uživatele za den z jednoho zdroje (ruční zápis, Apple Health, Strava…).
 * Za den a zdroj je jen jeden řádek – synchronizace ho přepisuje (upsert).
 * Nahrazuje tabulku Burned (burned_record), která neměla vazbu na uživatele.
 */
#[ORM\Entity(repositoryClass: ActivityEnergyRepository::class)]
#[ORM\Table(name: 'activity_energy')]
#[ORM\UniqueConstraint(name: 'UNIQ_ACTIVITY_USER_DATE_SOURCE', columns: ['user_id', 'date', 'source'])]
class ActivityEnergy
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_APPLE_HEALTH = 'apple_health';
    public const SOURCE_STRAVA = 'strava';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 20)]
    private string $source;

    #[ORM\Column]
    private int $activeKcal;

    // ID ve zdrojovém systému (např. Strava activity id), pokud existuje
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column]
    private \DateTimeImmutable $syncedAt;

    public function __construct(User $user, \DateTimeImmutable $date, string $source, int $activeKcal)
    {
        $this->user = $user;
        $this->date = $date->setTime(0, 0);
        $this->source = $source;
        $this->setActiveKcal($activeKcal);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function getActiveKcal(): int
    {
        return $this->activeKcal;
    }

    public function setActiveKcal(int $activeKcal): self
    {
        $this->activeKcal = max(0, $activeKcal);
        $this->syncedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getSyncedAt(): \DateTimeImmutable
    {
        return $this->syncedAt;
    }
}
