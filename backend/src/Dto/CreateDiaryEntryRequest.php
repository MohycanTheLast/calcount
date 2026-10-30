<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * POST /api/calculate – nový záznam z textu. Bez data se záznam uloží k dnešku, s datem jde o zpětný zápis.
 */
final class CreateDiaryEntryRequest
{
    // Delší text by zbytečně zvyšoval cenu volání AI (a do DB se stejně ukládá jen 255 znaků)
    public const MAX_FOOD_TEXT_LENGTH = 500;

    // Zpětný zápis do detailu dne – jak daleko zpět (stejně jako max. rozsah souhrnů dní)
    public const MAX_ENTRY_DAYS_BACK = 366;

    public function __construct(
        #[Assert\NotBlank(message: 'Napiš, co jsi snědl nebo vypil.', normalizer: 'trim')]
        #[Assert\Length(max: self::MAX_FOOD_TEXT_LENGTH, maxMessage: 'Text je příliš dlouhý (max. {{ limit }} znaků).')]
        public readonly string $food = '',
        #[Assert\Date(message: 'Neplatné datum, očekává se YYYY-MM-DD.')]
        public readonly ?string $date = null,
    ) {
    }

    // Den, ke kterému záznam patří; null = dnešek
    public function getEntryDate(): ?\DateTimeImmutable
    {
        if ($this->date === null || $this->date === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->date);

        return $date !== false && $date->format('Y-m-d') === $this->date ? $date : null;
    }

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        $date = $this->getEntryDate();
        if ($date === null) {
            return;
        }

        // Den uživatele může být o den napřed oproti serveru (časová pásma) – zítřek ještě povolíme
        $today = new \DateTimeImmutable('today');
        if ($date > $today->modify('+1 day') || $date < $today->modify(sprintf('-%d days', self::MAX_ENTRY_DAYS_BACK))) {
            $context->buildViolation(sprintf('Zapisovat jde jen do dnešního dne nebo nejvýš %d dní zpět.', self::MAX_ENTRY_DAYS_BACK))
                ->atPath('date')
                ->addViolation();
        }
    }
}
