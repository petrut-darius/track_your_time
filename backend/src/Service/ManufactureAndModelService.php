<?php

declare(strict_types = 1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ManufactureAndModelService {
    public function __construct(private string $projectDir)
    {
    }

    public function handle()
    {
        $data = (array) json_decode(file_get_contents($this->projectDir . "/manufacture_and_model.json"), true);
    
        $manufactures = [];

        foreach($data as $item) {
            $manufactures[$item["Make"]] = $item["Models"];
        }

        return $manufactures;
    }
}