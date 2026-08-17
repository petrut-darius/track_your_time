<?php

namespace App\MessageHandler;

use App\Action\ConfigurationAction;
use App\Message\ConfigurationActionMessage;
use App\Service\ConfigurationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConfigurationActionMessageHandler
{
    public function __construct(private ConfigurationService $configurationService, private ConfigurationAction $configurationAction)
    {

    }

    public function __invoke(ConfigurationActionMessage $configurationActionMessage): void
    {
        $configurations = $this->configurationService->handle();
        $this->configurationAction->handle($configurations);
    }
}
