<?php

namespace App\Entity;

use App\Entity\Security\User;
use App\Repository\DietDiaryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DietDiaryRepository::class)]
#[ORM\Table(name: "diet_record")]
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
