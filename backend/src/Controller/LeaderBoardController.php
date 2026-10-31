<?php

namespace App\Controller;

use App\Entity\CircuitTime;
use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LeaderBoardController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/api/leaderboard/{circuitId}', name: 'app_leader_board')]
    public function index(int $circuitId): Response
    {
        $circuitTimes = $this->em->getRepository(CircuitTime::class)->createQueryBuilder("c")
                                                                    ->andWhere("c.circuit = :circuit")
                                                                    ->setParameter("circuit", $circuitId)
                                                                    ->addOrderBy("c.minutes", "ASC")
                                                                    ->addOrderBy("c.seconds", "ASC")
                                                                    ->setMaxResults(10)
                                                                    ->getQuery()
                                                                    ->getResult();

        return $this->json([
            "data" => $circuitTimes
        ], Response::HTTP_OK, [], ["groups" => ["circuit-time:read"]]);
    }

    #[Route('/api/friends-leaderboard/{circuitId}', name: 'app_friend_leader_board')]
    public function index_friends(int $circuitId, #[CurrentUser] User $user): Response
    {
        $data = $this->em->getRepository(CircuitTime::class)
                        ->createQueryBuilder("ct")
                        ->join(Friendship::class, "f", "WITH", '(f.user = :currentUser and f.friend = ct.user) OR (f.friend = :currentUser and f.user = ct.user)')
                        ->andWhere("f.status = :status")
                        ->andWhere("ct.circuit = :circuit")
                        ->setParameter("currentUser", $user)
                        ->setParameter("status", "accepted")
                        ->setParameter("circuit", $circuitId)
                        ->addOrderBy("ct.minutes", "ASC")
                        ->addOrderBy("ct.seconds", "ASC")
                        ->getQuery()
                        ->getResult();

    

        return $this->json([
            "data" => $data
        ], Response::HTTP_OK, [], ["groups" => ["circuit-time:read"]]);
    }
}
