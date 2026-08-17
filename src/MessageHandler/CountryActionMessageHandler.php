<?php

namespace App\MessageHandler;

use App\Action\CountryAction;
use App\Message\CountryActionMessage;
use App\Service\CountryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CountryActionMessageHandler
{
    public function __construct(private CountryService $countryService, private CountryAction $countryAction)
    {

    }

    public function __invoke(CountryActionMessage $countryActionMessage): void
    {
        $countries = $this->countryService->handle();
        $this->countryAction->handle($countries);
    }
}
