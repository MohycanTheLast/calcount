<?php

namespace App\Model;

final class DietRecord
{
    public function __construct(
        public float $kcal,
        public float $waterInMl,
        public ?string $comment = null,
        public ?int $id = null,
    ) {}
}
