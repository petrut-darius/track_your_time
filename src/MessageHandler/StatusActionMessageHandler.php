<?php

namespace App\MessageHandler;

use App\Action\StatusAction;
use App\Message\StatusActionMessage;
use App\Service\StatusService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class StatusActionMessageHandler
{
    public function __construct(private StatusService $statusService, private StatusAction $statusAction)
    {

    }

    public function __invoke(StatusActionMessage $message): void
    {
        $statuses = $this->statusService->handle();
        $this->statusAction->handle($statuses);
    }
}
