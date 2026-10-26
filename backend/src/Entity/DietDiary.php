<?php

namespace App\Entity;

use App\Entity\Security\User;
use App\Repository\DietDiaryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DietDiaryRepository::class)]
#[ORM\Table(name: "diet_record")]
#[ORM\Index(name: "IDX_DIET_RECORD_USER_DATE", columns: ["user_id", "entry_date"])]
class DietDiary
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    protected User $user;

    #[ORM\Column(type: "string", nullable: false)]
    protected string $content;

    #[ORM\Column(type: "integer", nullable: false)]
    protected int $kcal;

    #[ORM\Column(type: "integer", nullable: false)]
    protected int $waterInMl;

    // Den, ke kterému záznam patří (místní datum uživatele). Oddělené od created_at, aby šlo zapisovat
    // i zpětně a deník/kalendář nezávisel na časové zóně serveru.
    #[ORM\Column(name: "entry_date", type: "date_immutable", nullable: false)]
    protected \DateTimeImmutable $entryDate;

    // Odhad AI: makroživiny v gramech a kofein v mg. null u záznamů z doby, kdy se ještě neodhadovaly.
    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $proteinG = null;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $carbsG = null;

    // Z toho cukry (část sacharidů)
    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $sugarG = null;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $fatG = null;

    #[ORM\Column(type: "integer", nullable: true)]
    protected ?int $caffeineMg = null;

    #[ORM\Column(name: "update_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $updatedAt;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): DietDiary
    {
        $this->id = $id;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): DietDiary
    {
        $this->content = $content;
        return $this;
    }

    public function getKcal(): int
    {
        return $this->kcal;
    }

    public function setKcal(int $kcal): DietDiary
    {
        $this->kcal = $kcal;
        return $this;
    }

    public function getWaterInMl(): int
    {
        return $this->waterInMl;
    }

    public function setWaterInMl(int $waterInMl): DietDiary
    {
        $this->waterInMl = $waterInMl;
        return $this;
    }

    public function getProteinG(): ?int
    {
        return $this->proteinG;
    }

    public function getCarbsG(): ?int
    {
        return $this->carbsG;
    }

    public function getSugarG(): ?int
    {
        return $this->sugarG;
    }

    public function getFatG(): ?int
    {
        return $this->fatG;
    }

    public function getCaffeineMg(): ?int
    {
        return $this->caffeineMg;
    }

    // Makroživiny a kofein se nastavují vždy společně (jeden odhad AI)
    public function setNutrients(?int $proteinG, ?int $carbsG, ?int $sugarG, ?int $fatG, ?int $caffeineMg): DietDiary
    {
        $this->proteinG = $proteinG;
        $this->carbsG = $carbsG;
        $this->sugarG = $sugarG;
        $this->fatG = $fatG;
        $this->caffeineMg = $caffeineMg;
        return $this;
    }

    public function getEntryDate(): \DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeImmutable $entryDate): DietDiary
    {
        $this->entryDate = $entryDate->setTime(0, 0);
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): DietDiary
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): DietDiary
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): DietDiary
    {
        $this->user = $user;
        return $this;
    }
}
