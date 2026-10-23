<?php

namespace App\Service;

use App\Entity\DietDiary;
use App\Entity\Security\User;
use App\Model\DietDiaryEntry;
use App\Model\DietDiaryEntryDay;
use App\Model\DietRecord;
use App\Repository\DietDiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class DietDiaryService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Security $security
    )
    {
    }

    public function create(string $text, DietRecord $record): DietDiary
    {
        // Případně další validace nebo odstranění HTML či nebezpečných znaků
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Oříznutí textu na 255 znaků
        $text = substr($text, 0, 255);

        $newItem = (new DietDiary())
            ->setContent($text)
            ->setKcal((int) round($record->kcal))
            ->setWaterInMl((int) round($record->waterInMl))
            ->setEntryDate(new \DateTimeImmutable('today'))
            ->setCreatedAt(new \DateTime())
            ->setUser($this->getLoggedUser())
        ;

        $this->entityManager->persist($newItem);
        $this->entityManager->flush();

        return $newItem;
    }

    public function update(DietDiary $record, array $data): void
    {
        if (isset($data['kcal'])) {
            $record->setKcal((int)$data['kcal']);
        }

        if (isset($data['waterInMl'])) {
            $record->setWaterInMl((int)$data['waterInMl']);
        }

        // Vlastník se nastavuje jen při vytvoření; úpravu cizího záznamu blokuje DietDiaryVoter
        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    public function delete(DietDiary $record): void
    {
        $this->entityManager->remove($record);
        $this->entityManager->flush();
    }

    /**
     * Původní formát pro /api/entries (frontend do refaktoru). Nové obrazovky mají používat /api/days.
     *
     * @return DietDiaryEntryDay[]
     */
    public function getAllEntries(): array
    {
        /** @var DietDiaryRepository $dietDiaryRepo */
        $dietDiaryRepo = $this->entityManager->getRepository(DietDiary::class);

        $entries = [];

        foreach ($dietDiaryRepo->getAggregatedEntriesByDay($this->getLoggedUser()) as $date => $totals) {
            $entries[] = new DietDiaryEntryDay(
                date: (new \DateTimeImmutable($date))->format('d.m.Y'),
                kcal: $totals['kcal'],
                // Výdej je v /api/days (BMR + aktivní kalorie podle zdroje)
                kcalBurned: 0,
                water: round($totals['waterMl'] / 1000, 2) // Voda v litrech
            );
        }

        return $entries;
    }

    /**
     * @param string $date
     * @return DietDiaryEntry[]
     */
    public function getEntriesByDateStr(string $date): array
    {
        /** @var DietDiaryRepository $dietDiaryRepo */
        $dietDiaryRepo = $this->entityManager->getRepository(DietDiary::class);

        $items = $dietDiaryRepo->getEntriesByDateStr($date, $this->getLoggedUser());

        $outItems = [];

        foreach ($items as $item) {
            $outItems[] = new DietDiaryEntry(
                $item->getId(),
                $item->getContent(),
                $item->getKcal(),
                $item->getWaterInMl()
            );
        }

        return $outItems;
    }

    public function getLoggedUser(): User
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user;
    }
}
