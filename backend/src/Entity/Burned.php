<?php

namespace App\Entity;

use App\Repository\BurnedRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BurnedRepository::class)]
#[ORM\Table(name: "burned_record")]
class Burned
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected int $id;

    #[ORM\Column(type: "string", nullable: true)]
    protected string $note;

    #[ORM\Column(type: "integer", nullable: false)]
    protected int $kcal;

    #[ORM\Column(name: "update_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $updatedAt;

    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Burned
    {
        $this->id = $id;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): Burned
    {
        $this->note = $note;
        return $this;
    }

    public function getKcal(): int
    {
        return $this->kcal;
    }

    public function setKcal(int $kcal): Burned
    {
        $this->kcal = $kcal;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): Burned
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): Burned
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
