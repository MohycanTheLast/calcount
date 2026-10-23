<?php

namespace App\Entity;

use App\Entity\Security\User;
use App\Repository\DietDiaryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DietDiaryRepository::class)]
#[ORM\Table(name: "user_settings")]
class UserSettings
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected int $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'settings')]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", unique: true, nullable: false)]
    protected User $user;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $avgDailyActivityKcal = null;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $bmrKcal = null;

    #[ORM\Column(type: "boolean", nullable: false)]
    protected bool $saveWeight = false;

    #[ORM\Column(type: "string", nullable: false)]
    protected string $themeMode = "dark";

    #[ORM\Column(type: "string", nullable: false)]
    protected string $aiCommentMode = "none";

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $weightKg = null;

    #[ORM\Column(name: "update_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $updatedAt;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserSettings
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserSettings
    {
        $this->user = $user;
        return $this;
    }

    public function getAvgDailyActivityKcal(): ?int
    {
        return $this->avgDailyActivityKcal;
    }

    public function setAvgDailyActivityKcal(?int $avgDailyActivityKcal): UserSettings
    {
        $this->avgDailyActivityKcal = $avgDailyActivityKcal;
        return $this;
    }

    public function getBmrKcal(): ?int
    {
        return $this->bmrKcal;
    }

    public function setBmrKcal(?int $bmrKcal): UserSettings
    {
        $this->bmrKcal = $bmrKcal;
        return $this;
    }

    public function isSaveWeight(): bool
    {
        return $this->saveWeight;
    }

    public function setSaveWeight(bool $saveWeight): UserSettings
    {
        $this->saveWeight = $saveWeight;
        return $this;
    }

    public function getThemeMode(): string
    {
        return $this->themeMode;
    }

    public function setThemeMode(string $themeMode): UserSettings
    {
        $this->themeMode = $themeMode;
        return $this;
    }

    public function getAiCommentMode(): string
    {
        return $this->aiCommentMode;
    }

    public function setAiCommentMode(string $aiCommentMode): UserSettings
    {
        $this->aiCommentMode = $aiCommentMode;
        return $this;
    }

    public function getWeightKg(): ?int
    {
        return $this->weightKg;
    }

    public function setWeightKg(?int $weightKg): UserSettings
    {
        $this->weightKg = $weightKg;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): UserSettings
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): UserSettings
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
