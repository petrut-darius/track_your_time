<?php

declare(strict_types = 1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GradingService
{
    const BASE_URL = "https://www.racingcircuits.info/";

    public function __construct(private HttpClientInterface $client)
    {

    }

    public function handle(): array
    {
        $response = $this->client->request("GET", self::BASE_URL . "find-a-circuit/circuit-grading.html");

        $crawler = new Crawler($response->getContent());

        $fiaGrades = $crawler->filter("#accordionEx h5")->each(function(Crawler $node) {
                                                                if(!($node->count() > 0)) {
                                                                    return "";
                                                                }

                                                                return ["name" => trim($node->text())];
                                                        });

        $fimGrades = $crawler->filter("#accordionEx2 h5")->each(function(Crawler $node) {
                                                                if(!($node->count() > 0)) {
                                                                    return "";
                                                                }

                                                                return ["name" => trim($node->text())];
                                                        });

        return array_merge($fiaGrades, $fimGrades);
    }
}
