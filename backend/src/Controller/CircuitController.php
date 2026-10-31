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

    #[Route('/api/circuits', name: 'app_api_circuits', methods: ["GET"])]
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
        $page = max(1, $request->query->getInt("page", $request->query->getInt("p", 1)));
        $limit = max(1, min(50, $request->query->getInt("limit", 10)));
        $search = trim((string)$request->query->get("q", ""));

        $queryBuilder = $this->em->getRepository(Circuit::class)->createQueryBuilder("c");

        if($search !== "") {
            $queryBuilder->andWhere("c.name LIKE :search OR c.address LIKE :search")
                ->setParameter("search", "%" . $search . "%");
        }

        $queryBuilder->setFirstResult(($page - 1) * $limit)
                        ->setMaxResults($limit)
                        ->orderBy("c.id", "ASC");

        $paginator = new Paginator($queryBuilder, fetchJoinCollection: true);
        $circuits = iterator_to_array($paginator->getIterator());

        $totalItems = count($paginator);
        $totalPages = (int) ceil($totalItems / $limit);

        return $this->json([
            "data" => $circuits,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => $totalItems,
                "totalPages" => $totalPages,
            ],
        ], Response::HTTP_OK, [], ["groups" => ["circuit:read"]]);
    }

    #[Route("/api/circuits/{id}", name: "app_api_circuits_show", methods: ["GET"])]
    public function show(Circuit $circuit): Response
    {
        return $this->json([
            "data" => $circuit,
        ], Response::HTTP_OK, [], ["groups" => ["circuit:read"]]);
    }
}
