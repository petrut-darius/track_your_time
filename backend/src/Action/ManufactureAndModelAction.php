<?php

declare(strict_types=1);

namespace App\Action;

use App\Entity\Manufacture;
use App\Entity\Model;
use App\Repository\ManufactureRepository;
use App\Repository\ModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ManufactureAndModelAction
{
    public function __construct(private ManufactureRepository $manufactureRepository, private ModelRepository $modelRepository, private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }


    public function handle(array $data): void
    {
        $this->em->wrapInTransaction(function(EntityManagerInterface $em) use ($data) {
            foreach($data as $data_manufacture => $models) {
                try {
                    $manufacture = $this->manufactureRepository->findOneBy(["name" => $data_manufacture]);

                    if($manufacture === null) {
                        $manufacture = new Manufacture();
                        $em->persist($manufacture);
                    }

                    $this->logger->debug("$data_manufacture before database insert", [
                        "name" => $data_manufacture,
                        "length" => mb_strlen($data_manufacture),
                    ]);

                    $manufacture->setName($data_manufacture);

                    foreach(array_unique($models) as $data_model) {
                        $model = $this->modelRepository->findOneBy(["name" => $data_model, "manufacture" => $manufacture]);

                        if($model === null) {
                            $model = new Model();
                            $em->persist($model);
                        }

                        $this->logger->debug("$data_model before database insert");

                        $model->setName($data_model);
                        $model->setManufacture($manufacture);
                    }


                } catch(\Exception $e) {
                    $this->logger->error(
                        "Manufacture or Model wasn't created or updated." . $e->getMessage(),
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