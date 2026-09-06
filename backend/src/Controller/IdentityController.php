<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IdentityController extends AbstractController
{
    #[Route('/api/me', name: 'app_api_me')]
    public function index(): Response
    {
        return $this->json([

        ], Response::HTTP_OK, ["groups" => "user:read"]);
    }
}
