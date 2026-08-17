<?php

declare(strict_types = 1);

namespace App\Action;

use App\Entity\Grade;
use App\Repository\GradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel("country")]//switch to grade
class GradeAction
{
    public function __construct(private GradeRepository $gradeRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function handle(array $grades): void 
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($grades): void {
            foreach ($grades as $gradeDTO) {
                $gradeName = trim($gradeDTO["name"]);
                try {
                    $grade = $this->gradeRepository->findOneBy(["name"=> $gradeName]);
                    
                    if($grade === null) {
                        $grade = new Grade();
                        $em->persist($grade);    
                    }

                    $grade->setName($gradeName);

                    //wrapInTransaction flushes before commiting
                } catch( \Exception $e) {
                    $this->logger->error(
                        "Grade($gradeName) wasn't updated or created " . $e->getMessage(),
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
