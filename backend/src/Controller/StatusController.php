<?php

namespace App\Controller;

use App\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/statuses', name: 'app_status')]
    public function index(): Response
    {
        $statuses = $this->em->getRepository(Status::class)->findAll();
        
        return $this->json([
            "data" => $statuses,
        ], Response::HTTP_OK, [], ["groups" => ["status:read"]]);
    }
}
