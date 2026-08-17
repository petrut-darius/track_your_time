<?php

declare(strict_types = 1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StatusService
{
    const BASE_URL = "https://www.racingcircuits.info/";

    public function __construct(private HttpClientInterface $client)
    {

    }

    public function handle(): array
    {
        $response = $this->client->request("GET", self::BASE_URL . "find-a-circuit/circuit-status.html");

        $crawler = new Crawler($response->getContent());

        $statuses = $crawler->filter("#myTabEx li")->each(function(Crawler $node) {
                                                                if(!($node->count() > 0)) {
                                                                    return "";
                                                                }

                                                                return ["name" => $node->text()];                    
                                                        });

        return (array) $statuses;
    }
}
