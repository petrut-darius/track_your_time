<?php

namespace App\Controller;

use App\Entity\Grade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GradeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/grades', name: 'app_api_grades', methods: ["GET"])]
    public function index(): Response
    {
        $grades = $this->em->getRepository(Grade::class)->findAll();

        return $this->json([
            "data" => $grades,
        ], Response::HTTP_OK, [], ["groups" => ["grade:read"]]);
    }
}
