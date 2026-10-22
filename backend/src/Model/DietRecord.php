<?php

namespace App\Model;

final class DietRecord
{
    public function __construct(
        public ?int $id = null,
        public float $kcal,
        public float $waterInMl,
        public ?string $comment = null,
    ) {}
}
