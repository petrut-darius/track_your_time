<?php

namespace App\Controller;

use App\Service\CountryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CountryController extends AbstractController
{
    #[Route('/api/countries', name: 'app_api_country')]
    public function index(CountryService $countryService): JsonResponse
    {
        return $this->json([
            $countryService->handle(),
        ]);
    }
}
