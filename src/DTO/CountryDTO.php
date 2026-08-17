<?php

declare(strict_types = 1);

namespace App\DTO;
// 
final class CountryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $imageUrl,
        public readonly string $url,
        public readonly array $circuits,
    )
    {
        //
    }
}