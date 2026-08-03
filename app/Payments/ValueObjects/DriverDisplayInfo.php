<?php

namespace App\Payments\ValueObjects;

final class DriverDisplayInfo
{
    public function __construct(
        public readonly string $label,
        public readonly string $description,
    ) {}
}
