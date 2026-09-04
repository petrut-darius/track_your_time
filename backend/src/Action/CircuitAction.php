<?php

declare(strict_types = 1);

namespace App\Action;

use App\Entity\Circuit;
use App\Entity\Grade;
use App\Repository\CircuitRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\CountryRepository;
use App\Repository\GradeRepository;
use App\Repository\StatusRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel(channel: "country")]//switch to circuit
class CircuitAction
{
    public function __construct(private CountryRepository $countryRepository,
                                private StatusRepository $statusRepository,
                                private TypeRepository $typeRepository,
                                private GradeRepository $gradeRepository,                           
                                private CircuitRepository $circuitRepository, 
                                private EntityManagerInterface $em,
                                private LoggerInterface $logger)
    {

    }

    public function handle(array $circuits): void
    {
        $countries = $this->countryRepository->findAllIndexedByName();
        $statuses = $this->statusRepository->findAllIndexedByName();
        $types = $this->typeRepository->findAllIndexedByName();
        //$configurations = $this->configurationRepository->findAllIndexedByName();
        $grades = $this->gradeRepository->findAllIndexedByName();

        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($circuits, $countries, $statuses, $types, $grades): void {//add the $configuration var to the use() if I somehow make the system work
            foreach( $circuits as $circuitDTO) {
                try {
                    $circuit = $this->circuitRepository->findOneBy(["name" => $circuitDTO->name]);

                    if($circuit === null) {
                        $circuit = new Circuit();
                        $em->persist($circuit);    
                    }
                    
                    $this->logger->debug('Name before database insert', [
                        'name' => $circuitDTO->name,
                        'length' => mb_strlen($circuitDTO->name),
                        'max_length' => 150,
                    ]);

                    $circuit->setName($circuitDTO->name);
                    $circuit->setUrl($circuitDTO->url);
                    $circuit->setImageUrl($circuitDTO->imageUrl);
                    $circuit->setAddress($circuitDTO->address);
                    $circuit->setOverview($circuitDTO->overview);
                    $circuit->setLength($circuitDTO->length);

                    //country
                    $countryName = str_replace("-", " ", mb_strtolower($circuitDTO->circuitCountry));
                    $circuitCountry = $countries[$countryName];

                    if($circuitCountry) {
                        $circuit->setCountry($circuitCountry);
                    }else{
                        $this->logger->error(
                            "The Country($circuitDTO->circuitCountry) for the Circuit($circuitDTO->name) could not be resolved.",
                        );
                        
                        throw new \InvalidArgumentException();
                    }

                    //tags
                    foreach($circuitDTO->status as $status) {
                        if($statuses[$status]) {
                            $circuit->addStatus($statuses[$status]);
                        }else{
                            $this->logger->error(
                                "The Status($status) for the Circuit($circuitDTO->name) could not be resolved.",
                            );

                            throw new \InvalidArgumentException();
                        }
                    }


                    foreach($circuitDTO->type as $type) {
                        if($types[$type]) {
                            $circuit->addType($types[$type]);
                        }else{
                            $this->logger->error(
                                "The Type($type) for the Circuit($circuitDTO->name) could not be resolved.",
                            );

                            throw new \InvalidArgumentException();
                        }
                    }

                    /*
                    foreach($circuitDTO->configuration as $configuration) {
                        if($configurations[$configuration]) {
                            $circuit->addConfiguration($configurations[$configuration]);
                        }else{
                            $this->logger->error(
                                "The Configuration($configuration) for the Circuit($circuitDTO->name) could not be resolved.",
                            );

                            throw new \InvalidArgumentException();
                        }
                    }
                    */

                    foreach($circuitDTO->grading as $grade) {
                        $gradeName = trim($grade);

                        if (!isset($grades[$gradeName])) {
                            $this->logger->warning(
                                "Grade($gradeName) for Circuit($circuitDTO->name, $circuitDTO->url) was not present in the grade catalogue; creating it.",
                            );

                            $this->logger->debug('Grade name before database insert', [
                                'name' => $gradeName,
                                'length' => mb_strlen($gradeName),
                                'max_length' => 150,
                            ]);

                            $grades[$gradeName] = (new Grade())->setName($gradeName);
                            $em->persist($grades[$gradeName]);
                        }

                        $circuit->addGrade($grades[$gradeName]);
                    }

                } catch(\Exception $e) {
                    $this->logger->error(
                        "Circuit($circuitDTO->name) wasn't updated or created " . $e->getMessage(),
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
