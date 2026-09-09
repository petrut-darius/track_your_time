<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FriendController extends AbstractController
{
    #[Route('/friend', name: 'app_friend')]
    public function index(): Response
    {
        return $this->render('friend/index.html.twig', [
            'controller_name' => 'FriendController',
        ]);
    }
}
