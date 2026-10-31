<?php

namespace App\Controller;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TypeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/types', name: 'app_type')]
    public function index(): Response
    {
        $types = $this->em->getRepository(Type::class)->findAll();

        return $this->json([
            "data" => $types,
        ], Response::HTTP_OK, [], ["groups" => ["type:read"]]);
    }
}
