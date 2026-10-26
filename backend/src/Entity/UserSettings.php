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

    /**
     * Odkud brát aktivní kalorie dne (viz DaySummaryService):
     * static = průměrná denní aktivita (avgDailyActivityKcal), synced = jen synchronizovaná data (Health, Strava…),
     * synced_or_static = synchronizovaná data, a pokud za den chybí, průměrná aktivita.
     * Ruční zápis za konkrétní den (zdroj „manual“) má vždy přednost.
     */
    public const ACTIVITY_SOURCE_STATIC = 'static';
    public const ACTIVITY_SOURCE_SYNCED = 'synced';
    public const ACTIVITY_SOURCE_SYNCED_OR_STATIC = 'synced_or_static';
    public const ACTIVITY_SOURCES = [self::ACTIVITY_SOURCE_STATIC, self::ACTIVITY_SOURCE_SYNCED, self::ACTIVITY_SOURCE_SYNCED_OR_STATIC];

    #[ORM\Column(length: 20, nullable: false, options: ['default' => self::ACTIVITY_SOURCE_STATIC])]
    protected string $activitySource = self::ACTIVITY_SOURCE_STATIC;

    // Pokročilé: u zadávání jídla i tlačítko pro výběr fotky z galerie (jinak jen vyfocení)
    #[ORM\Column(type: "boolean", nullable: false, options: ['default' => false])]
    protected bool $photoGalleryEnabled = false;

    // Pokročilé – hlídání pitného režimu proti dennímu cíli (null = výchozí cíl na frontendu)
    #[ORM\Column(type: "boolean", nullable: false, options: ['default' => false])]
    protected bool $hydrationEnabled = false;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $waterGoalMl = null;

    // Pokročilé, jen některé typy účtů (AccountType::FEATURE_CAFFEINE) – hlídání kofeinu proti dennímu limitu
    #[ORM\Column(type: "boolean", nullable: false, options: ['default' => false])]
    protected bool $caffeineEnabled = false;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $caffeineLimitMg = null;

    // Pokročilé, jen některé typy účtů (AccountType::FEATURE_MACROS) – zobrazení makroživin
    #[ORM\Column(type: "boolean", nullable: false, options: ['default' => false])]
    protected bool $macrosEnabled = false;

    #[ORM\Column(name: "update_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $updatedAt;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getActivitySource(): string
    {
        return $this->activitySource;
    }

    public function setActivitySource(string $activitySource): UserSettings
    {
        if (!in_array($activitySource, self::ACTIVITY_SOURCES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown activity source "%s"', $activitySource));
        }

        $this->activitySource = $activitySource;
        return $this;
    }

    public function isPhotoGalleryEnabled(): bool
    {
        return $this->photoGalleryEnabled;
    }

    public function setPhotoGalleryEnabled(bool $photoGalleryEnabled): UserSettings
    {
        $this->photoGalleryEnabled = $photoGalleryEnabled;
        return $this;
    }

    public function isHydrationEnabled(): bool
    {
        return $this->hydrationEnabled;
    }

    public function setHydrationEnabled(bool $hydrationEnabled): UserSettings
    {
        $this->hydrationEnabled = $hydrationEnabled;
        return $this;
    }

    public function getWaterGoalMl(): ?int
    {
        return $this->waterGoalMl;
    }

    public function setWaterGoalMl(?int $waterGoalMl): UserSettings
    {
        $this->waterGoalMl = $waterGoalMl;
        return $this;
    }

    public function isCaffeineEnabled(): bool
    {
        return $this->caffeineEnabled;
    }

    public function setCaffeineEnabled(bool $caffeineEnabled): UserSettings
    {
        $this->caffeineEnabled = $caffeineEnabled;
        return $this;
    }

    public function getCaffeineLimitMg(): ?int
    {
        return $this->caffeineLimitMg;
    }

    public function setCaffeineLimitMg(?int $caffeineLimitMg): UserSettings
    {
        $this->caffeineLimitMg = $caffeineLimitMg;
        return $this;
    }

    public function isMacrosEnabled(): bool
    {
        return $this->macrosEnabled;
    }

    public function setMacrosEnabled(bool $macrosEnabled): UserSettings
    {
        $this->macrosEnabled = $macrosEnabled;
        return $this;
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
