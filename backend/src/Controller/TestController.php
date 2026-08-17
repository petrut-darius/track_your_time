<?php

namespace App\Controller;

use App\Service\CircuitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


final class TestController extends AbstractController
{
    #[Route('/api/test', name:'app_test', methods: ["GET"])]
    public function test(CircuitService $service): JsonResponse
    {
        return $this->json([
            "message" => "Hello Angular from Symfony.",
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