<?php

declare(strict_types = 1);

namespace App\Service;

use App\DTO\CircuitDTO;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CircuitService
{
    const BASE_URL = "https://www.racingcircuits.info/";

    public function __construct(private HttpClientInterface $client, private CountryService $countryService)
    {

    }

    public function handle()//:CircuitDTO 
    {
        $countries = $this->countryService->handle();

        $circuitsResponses = [];

        foreach($countries as $country) {
            foreach($country->circuits as $circuit) {
                $circuitsResponses[] = [ 
                    "circuitUrl" => self::BASE_URL . $circuit["url"],
                    "response" => $this->client->request("GET", self::BASE_URL . $circuit["url"]),
                    ];
                }
        }
        
        $circuits = $this->getCircuits($circuitsResponses);
            
        return $circuits;
    }

    private function getCircuits(array $responses): array
    {
        $circuits = [];

        foreach($responses as $response) {
            $circuitUrl = $response["circuitUrl"];
            $crawler = new Crawler($response["response"]->getContent());

            $circuitCountryName = explode("/", $circuitUrl)[4];
            $circuitFirstName = mb_convert_case(str_replace("-", " ",explode(".", explode("/", $circuitUrl)[5])[0]), MB_CASE_TITLE, "UTF-8");

            $circuitOverview = $crawler->filter("div.col-md-8.mt-2.ml-0 p")->slice(0, 3)->each(function(Crawler $node) {
                                                                                return [
                                                                                    "content" => $node->text(),
                                                                                ];
                                                                            });
            
            $circuitTags = [];

            $crawler->filter(".chip a")->each(function(Crawler $node) use (&$circuitTags) {
                                        $file = basename($node->attr("href"), ".html");
                                        $tag = explode("-", $file)[1];
                                        $circuitTags[$tag][] = trim($node->text());
                                    });

            $circuitStatus = [];
            $circuitType = [];
            //$circuitConfiguration = [];
            $circuitGrading = [];

            foreach($circuitTags as $key => $value) {
                switch($key) {
                    case "status":
                        $circuitStatus =  $value;
                        break;
                    case "type":
                        $circuitType = $value;
                        break;
                    //case "configuration":
                        //  $circuitConfiguration = $value;
                    //    break;
                    case "grading":
                        $circuitGrading = $value;
                        break;
                }
            }

            $images = [];
            $data = [];

            $images = $crawler->filter("#maps > .tab-content > .tab-pane.active img")->each(function(Crawler $node) {
                                                            return [
                                                                "url" => self::BASE_URL . $node->attr("src")
                                                            ];
                                                        });
                    
            $data = $crawler->filter("#maps > .tab-content > .tab-pane.active .list-group-item")->each(function(Crawler $node) {
                                                            return [
                                                                "name" => trim($node->filter("strong")->text()),
                                                                "distance" => trim($node->filter("small")->text()),
                                                            ];
                                                        });

            $addressNode = $crawler->filter("div.card-body dl.row > dd.col-sm-10")->first();

            $circuitAddress = $addressNode->count() > 0 ? trim($addressNode->text()) : null;

            $circuitCountryImageUrl = $crawler->filter("img.float-right.mt-1")->attr("src");

            foreach($images as $index => $image) {
                $circuitFullName = $circuitFirstName . " " . $data[$index]["name"];

                $circuits[] = new CircuitDTO(
                    name: $circuitFullName ?? "",
                    url: $circuitUrl ?? "", // sa scrie in loc de spatii albe dinalea %20%
                    circuitCountry: $circuitCountryName ?? "",
                    imageUrl: $image['url'] ?? "",
                    countryImage: self::BASE_URL . $circuitCountryImageUrl,
                    length: $data[$index]['distance'] ?? "",
                    overview: $circuitOverview ?? "",
                    address: $circuitAddress ?? '',
                    status: $circuitStatus ?? "",
                    type: $circuitType ?? "",
//                    configuration: $circuitConfiguration ?? "",
                    grading: $circuitGrading ?? "",
                );
            }
        }

        return $circuits;
    }

    public function getCircuitTest(string $href)//: CircuitDTO
    {
        $circuits = [];

            $response = $this->client->request("GET", $href);

            $crawler = new Crawler($response->getContent());

            $countryName = explode("/", $href)[5];

            dump($countryName, explode("/", $href));

            $circuits = [
                "countryName" => $countryName,
            ];

            dump($circuits);

            return $circuits;
    }
}
