<?php

namespace App\Service;

use App\Dto\UpdateDiaryEntryRequest;
use App\Entity\DietDiary;
use App\Entity\Security\User;
use App\Model\DietDiaryEntry;
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

    // $entryDate = den, ke kterému záznam patří (zpětný zápis); null = dnešek
    public function create(string $text, DietRecord $record, ?\DateTimeImmutable $entryDate = null): DietDiary
    {
        // Text se ukládá tak, jak ho uživatel napsal. Escapuje se až při výstupu (frontend, JSON),
        // jinak by se v detailu zobrazovaly entity typu &quot; a nešlo by v textu vyhledávat.
        // Oříznutí na délku sloupce (255 znaků) po znacích, ne po bajtech – nerozbije diakritiku.
        $text = mb_substr(trim($text), 0, 255);

        $newItem = (new DietDiary())
            ->setContent($text)
            ->setKcal((int) round($record->kcal))
            ->setWaterInMl((int) round($record->waterInMl))
            ->setEntryDate($entryDate ?? new \DateTimeImmutable('today'))
            ->setCreatedAt(new \DateTime())
            ->setUser($this->getLoggedUser())
            ->setNutrients(
                $this->roundAmount($record->proteinG),
                $this->roundAmount($record->carbsG),
                $this->roundAmount($record->sugarG),
                $this->roundAmount($record->fatG),
                $this->roundAmount($record->caffeineMg),
            )
        ;

        $this->entityManager->persist($newItem);
        $this->entityManager->flush();

        return $newItem;
    }

    public function update(DietDiary $record, UpdateDiaryEntryRequest $values): void
    {
        if ($values->kcal !== null) {
            $record->setKcal((int) round($values->kcal));
        }

        if ($values->waterInMl !== null) {
            $record->setWaterInMl((int) round($values->waterInMl));
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
                $item->getWaterInMl(),
                $item->getProteinG(),
                $item->getCarbsG(),
                $item->getSugarG(),
                $item->getFatG(),
                $item->getCaffeineMg(),
            );
        }

        return $outItems;
    }

    // Odhad AI na celé jednotky; záporné hodnoty (chyba modelu) jako 0
    private function roundAmount(?float $value): ?int
    {
        return $value === null ? null : max(0, (int) round($value));
    }

    public function getLoggedUser(): User
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user;
    }
}
