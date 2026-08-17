<?php

declare(strict_types = 1);

namespace App\Action;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel("country")]//switch to configuration
class ConfigurationAction
{
    public function __construct(private ConfigurationRepository $configurationRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function handle(array $configurations): void 
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($configurations): void {
            foreach ($configurations as $configurationDTO) {
                $configurationName = $configurationDTO["name"];
                try {
                    $configuration = $this->configurationRepository->findOneBy(["name"=> $configurationName]);
                    
                    if($configuration === null) {
                        $configuration = new Configuration();
                        $em->persist($configuration);    
                    }

                    $configuration->setName($configurationName);

                    //wrapInTransaction flushes before commiting
                } catch( \Exception $e) {
                    $this->logger->error(
                        "Configuration($configurationName) wasn't updated or created" . $e->getMessage(),
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
