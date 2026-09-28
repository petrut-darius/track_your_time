<?php

namespace App\Controller;

use App\DTO\CircuitDTO;
use App\Entity\Circuit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CircuitController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/circuits', name: 'app_api_circuit', methods: ["GET"])]
    #[OA\Tag("Circuits")]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "List of all circuits in the db",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: CircuitDTO::class))
        )
    )]
    public function index(Request $request): Response
    {
        $page = max(0, $request->query->getInt("p", 0));

        $query = $this->em->getRepository(Circuit::class)->createQueryBuilder("c")
                                                            ->setFirstResult($page * 10 + 1)
                                                            ->setMaxResults(10);

        $paginator = new Paginator($query, fetchJoinCollection: true);
        $circuits = iterator_to_array($paginator->getIterator());

        return $this->json([
            "data" => $circuits,
        ], Response::HTTP_OK, [], ["groups" => ["circuit:read"]]);
    }
}
