<?php

declare(strict_types = 1);

namespace App\Service;

use App\DTO\CircuitDTO;
use App\DTO\CountryDTO;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountryService
{
    const BASE_URL = "https://www.racingcircuits.info/";

    public function __construct(private HttpClientInterface $client)
    {

    }
    public function handle(): array 
    {
        $response = $this->client->request("GET", self::BASE_URL . "europe/");
         
        $crawler = new Crawler($response->getContent());

        $linkNodes = $crawler->filter(".col-md-8 > div a");

        //every country page html content
        $countryResponses = [];
        
        foreach($linkNodes as $linkNode) {
            $href = $linkNode->attributes->item(0)->nodeValue;

            $countryResponses[] = [
                                    "url" => self::BASE_URL . $href,
                                    "response" =>$this->client->request("GET", self::BASE_URL . $href)
                                    ];
        }

        $countries = $this->getCountries($countryResponses);
    
        return $countries;
    }

    private function getCountries(array $responses): array
    {
        $countries = [];

        foreach($responses as $response) {
            $countryUrl = $response["url"];
            $Crawler = new Crawler($response["response"]->getContent());

            $countryName = $Crawler->filter(".font-weight-bold.mb-n1")->text();

            $circuits = $Crawler->filter("ul.tracks li.card")
                                                    ->reduce(function( Crawler $node) {
                                                        return $node->filter("em")->count() === 0;
                                                    })->filter("a.h6")
                                                    ->each(function(Crawler $node) {
                                                        return [
                                                            "url" => $node->attr("href"),
                                                        ];
                                                    });

            $countryImageUrl = $Crawler->filter("img.float-right.mt-1")->attr("src");

            $countries[] = new CountryDTO(
                name: $countryName,
                imageUrl: $countryImageUrl,
                url: $countryUrl,
                circuits: $circuits,
            );
        }

        return $countries;
    }

    public function getCountryTest(string $href)//:CountryDTO
    {
        $countries = [];

        $response = $this->client->request("GET", $href);

        $Crawler = new Crawler($response->getContent());

        $countryName = $Crawler->filter(".font-weight-bold.mb-n1")->text();

        $circuits = $Crawler->filter("ul.tracks li.card")
                                                ->reduce(function( Crawler $node) {
                                                    return $node->filter("em")->count() === 0;
                                                })->filter("a.h6")
                                                ->each(function(Crawler $node) {
                                                    return [
                                                        "url" => $node->attr("href"),
                                                    ];
                                                });

        $imageUrl = $Crawler->filter("img.float-right.mt-1")->attr("src");

        $countries[] = [
            "name" => mb_strtolower($countryName),
            "imageUrl" =>  $imageUrl,
            "circuits" => $circuits,
        ];


        dump($countries);
    }
}