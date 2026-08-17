<?php

declare(strict_types= 1);

namespace App\Action;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel(channel: "country")]
class CountryAction {

    public function __construct(private CountryRepository $countryRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function handle(array $countries): void 
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($countries): void {
            foreach ($countries as $countryDTO) {
                try {
                    $country = $this->countryRepository->findOneBy(["name"=> $countryDTO->name]);
                    
                    if($country === null) {
                        $country = new Country();
                        $em->persist($country);    
                    }

                    $country->setName(mb_strtolower($countryDTO->name));
                    $country->setUrl($countryDTO->url);
                    $country->setImageUrl($countryDTO->imageUrl);

                    //wrapInTransaction flushes before commiting
                } catch( \Exception $e) {
                    $this->logger->error(
                        "Country($countryDTO->name) wasn't updated or created" . $e->getMessage(),
                        [
                            "line" => $e->getLine(),
                            "exception_code" => $e->getCode(),
                            "file" => $e->getFile(),
                            "trace" => $e->getTraceAsString(),
                            "message" => $e->getMessage(),
                        ]
                    );
                    throw $e;
                }
            }
        });
    }
}