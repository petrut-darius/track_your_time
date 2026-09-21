<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class FriendController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route("/api/friends", name: "app_api_friend", methods: ["GET"])]
    public function index(#[CurrentUser] User $user): Response
    {
        $friendships = $this->em->getRepository(Friendship::class)->createQueryBuilder('f')
                                                                    ->andWhere("f.status = :status")
                                                                    ->andWhere("f.user = :user OR f.friend = :user")
                                                                    ->setParameter("status", "accepted")
                                                                    ->setParameter("user", $user)
                                                                    ->getQuery()
                                                                    ->getResult();

        $data = array_map(fn(Friendship $friendship) => [
            "id" => $friendship->getId(),
            "status" => $friendship->getStatus(),
            "friend" => $friendship->getOtherUser($user),
        ], $friendships);

        return $this->json(["data" => $data], Response::HTTP_OK, [], ["groups" => "friendship:read"]);
    }

    #[Route("/api/friend-requests", name: "app_api_friend_requests", methods: ["GET"])]
    public function getFriendRequests(#[CurrentUser] User $user): Response
    {
        $friendRequests = $this->em->getRepository(Friendship::class)->createQueryBuilder("f")
                                                                    ->andWhere("f.status = :status")
                                                                    ->andWhere("f.friend = :user")
                                                                    ->setParameter("status", "pending")
                                                                    ->setParameter("user", $user)
                                                                    ->getQuery()
                                                                    ->getResult();

        $data = array_map(fn(Friendship $friendship) => [
            "id" => $friendship->getId(),
            "status" => $friendship->getStatus(),
            "friend" => $friendship->getOtherUser($user),
        ], $friendRequests);

        return $this->json(["data" => $data], Response::HTTP_OK, [], ["groups" => "friendship:read"]);
    }

    #[Route('/api/add-friend/{id}', name: 'app_api_friend_create', methods: ["POST"], requirements: ["id" => "\d+"])]
    public function create(#[CurrentUser] User $user, #[MapEntity(mapping: ["id" => "id"])] User $friend): Response
    {
        if($this->em->getRepository(Friendship::class)->findOneBy(["user" => $user, 'friend' => $friend])) {
            return $this->json([
                "error" => "The friend request is pending."
            ], Response::HTTP_CONFLICT);
        }

        $friendship = new Friendship();
        $friendship->setUser($user)
                    ->setFriend($friend)
                    ->setStatus("pending");

        $this->em->persist($friendship);
        $this->em->flush();

        return $this->json([
            "data" => [
                "message" => "Friend request pending.",
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route("/api/update-friend/{id}", name: "app_api_friend_update", methods: ["PATCH"], requirements: ["id" => "\d+"])]
    public function update(#[CurrentUser] User $user, Friendship $friendship): Response
    {
        if(!$friendship) {
            throw $this->createNotFoundException();
        }

        if($friendship->getFriend() !== $user) {
            throw new AccessDeniedException();
        }

        $friendship->setStatus("accepted");

        $this->em->flush();

        return $this->json([
            "data" => [
                "message" => "Friend request accepted.",
            ]
        ], Response::HTTP_OK);
    }

    #[Route("/api/remove-friend/{id}", name: "app_api_friend_delete", methods: ["DELETE"], requirements: ["id" => "\d+"])]
    public function delete(#[CurrentUser] User $user, Friendship $friendship): Response
    {
        if(!$friendship) {
            throw $this->createNotFoundException();
        }

        if($friendship->getFriend() !== $user) {
            throw new AccessDeniedException();
        }

        $this->em->remove($friendship);
        $this->em->flush();

        return $this->json([

        ], Response::HTTP_OK);
    }
}
