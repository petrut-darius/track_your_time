<?php

namespace App\MessageHandler;

use App\Action\ManufactureAndModelAction;
use App\Message\ManufactureAndModelActionMessage;
use App\Service\ManufactureAndModelService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ManufactureAndModelActionMessageHandler{
    public function __construct(private ManufactureAndModelService $manufactureAndModelService, private ManufactureAndModelAction $manufactureAndModelAction){
    }

    public function __invoke(ManufactureAndModelActionMessage $manufactureAndModelActionMessage): void
    {
        $data = $this->manufactureAndModelService->handle();
        $this->manufactureAndModelAction->handle($data);
    }
}
