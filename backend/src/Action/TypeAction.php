<?php

declare(strict_types = 1);

namespace App\Action;

use App\Entity\Type;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel("country")]//switch to type
class TypeAction
{
    public function __construct(private TypeRepository $typeRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function handle(array $types): void 
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($types): void {
            foreach ($types as $typeDTO) {
                $typeName = $typeDTO["name"];
                try {
                    $type = $this->typeRepository->findOneBy(["name"=> $typeName]);
                    
                    if($type === null) {
                        $type = new Type();
                        $em->persist($type);    
                    }

                    $type->setName($typeName);

                    //wrapInTransaction flushes before commiting
                } catch( \Exception $e) {
                    $this->logger->error(
                        "Type($typeName) wasn't updated or created " . $e->getMessage(),
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
