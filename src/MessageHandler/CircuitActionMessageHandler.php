<?php

namespace App\MessageHandler;

use App\Action\CircuitAction;
use App\Message\CircuitActionMessage;
use App\Service\CircuitService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CircuitActionMessageHandler
{
    public function __construct(private CircuitService $circuitService, private CircuitAction $circuitAction)
    {

    }

    public function __invoke(CircuitActionMessage $message): void
    {
        $circuits = $this->circuitService->handle();
        $this->circuitAction->handle($circuits);
    }
}
