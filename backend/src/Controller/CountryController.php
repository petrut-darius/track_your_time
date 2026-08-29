<?php

namespace App\Controller;

use App\DTO\CountryDTO;
use App\Service\CountryService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

final class CountryController extends AbstractController
{
    #[Route('/api/countries', name: 'app_api_country')]
    #[OA\Tag(name: "Countries")]
    #[Security(name: "Bearer")]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "List of all countries in the db",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: CountryDTO::class))
        )
    )]
    #[OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Invalid or missing JWT")]
    public function index(CountryService $countryService): JsonResponse
    {
        return $this->json([
            $countryService->handle(),
        ]);
    }
}
