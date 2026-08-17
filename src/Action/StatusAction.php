<?php

declare(strict_types = 1);

namespace App\Action;

use App\Entity\Status;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel("country")]//switch to status
class StatusAction
{
    public function __construct(private StatusRepository $statusRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function handle(array $statuses): void 
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($statuses): void {
            foreach ($statuses as $statusDTO) {
                $statusName = $statusDTO["name"];
                try {
                    $status = $this->statusRepository->findOneBy(["name"=> $statusName]);
                    
                    if($status === null) {
                        $status = new Status();
                        $em->persist($status);    
                    }

                    $status->setName($statusName);

                    //wrapInTransaction flushes before commiting
                } catch( \Exception $e) {
                    $this->logger->error(
                        "Status($statusName) wasn't updated or created " . $e->getMessage(),
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
