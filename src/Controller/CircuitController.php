<?php

namespace App\Controller;

use App\Service\CircuitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CircuitController extends AbstractController
{
    #[Route('/api/circuits', name: 'app_api_circuit')]
    public function index(CircuitService $circuitService): JsonResponse
    {
        return $this->json([
            $circuitService->handle(),
        ]);
    }
}
