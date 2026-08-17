<?php

namespace App\Controller;

use App\Service\CircuitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


final class TestController extends AbstractController
{
    #[Route('/api/test', name:'app_test')]
    public function test(CircuitService $service): JsonResponse
    {
        return $this->json([
            $service->getCircuitTest("https://www.racingcircuits.info/europe/romania/motorpark-romania.html"),
        ]);
    }

    #[Route("/api/secret", name:"app_api_secret")]
    public function secret(): JsonResponse
    {
        return $this->json([
            "message" => "this is a secret "
        ]);
    }

}