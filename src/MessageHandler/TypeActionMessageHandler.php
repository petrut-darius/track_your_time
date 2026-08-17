<?php

namespace App\MessageHandler;

use App\Action\TypeAction;
use App\Message\TypeActionMessage;
use App\Service\TypeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TypeActionMessageHandler
{
    public function __construct(private TypeAction $typeAction, private TypeService $typeService)
    {

    }

    public function __invoke(TypeActionMessage $message): void
    {
        $types = $this->typeService->handle();
        $this->typeAction->handle($types);
    }
}
