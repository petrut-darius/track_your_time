<?php

declare(strict_types = 1);

namespace App\DTO;

final class CircuitDTO
{
    public function __construct(
        public readonly string $name,//good //on db entity
        public readonly string $url,//good //on db entity
        public readonly string $circuitCountry,//good
        public readonly string $imageUrl,//good //on db entity
        public readonly string $countryImage,//good
        public readonly string $length,//good //on db entity
        public readonly array $overview,//good //on db entity
        public readonly string $address,//good //on db entity
        public readonly array $type,//good //ManyToMany
        public readonly array $status,//good //ManyToMany
       // public readonly array $configuration,//good //ManyToMany
        public readonly array $grading,//good //ManyToMany
    )
    {
        //
    }
}