<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

final class IdentityController extends AbstractController
{
    #[Route('/api/me', name: 'app_api_me', methods: ["GET"])]
    #[IsGranted("IS_FULLY_AUTHENTICATED")]
    #[OA\Tag(name: "Identity")]
    #[OA\Response(response: Response::HTTP_OK, description: "Successfully sent your user data")]
    #[OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Not authenticated")]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->json([
            "data" => $user,
        ], Response::HTTP_OK, ["groups" => "user:read"]);
    }
}
