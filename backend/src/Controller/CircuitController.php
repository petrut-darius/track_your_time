<?php

namespace App\Controller;

use App\DTO\CircuitDTO;
use App\Service\CircuitService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class CircuitController extends AbstractController
{
    #[Route('/api/circuits', name: 'app_api_circuit', methods: ["GET"])]
    #[OA\Tag("Circuits")]
    #[OA\Response(
        response: JsonResponse::HTTP_OK,
        description: "List of all circuits in the db",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: CircuitDTO::class))
        )
    )]
    public function index(CircuitService $circuitService): JsonResponse
    {
        return $this->json([
            $circuitService->handle(),
        ], JsonResponse::HTTP_OK);
    }
}
