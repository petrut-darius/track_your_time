<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class ProfileController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $em, private SerializerInterface $serializer)
    {
    }

    #[Route('/api/user/{id}', name: 'app_api_profile', requirements: ["id" => "\d+"], methods: ["GET"])]
    #[IsGranted("AUTHENTICATED_FULLY")]
    public function index(#[CurrentUser] User $user): Response
    {
        //not sure if I need this if I have that observable that gets the user data on APP_INITIALIZER
        return $this->json([
            "data" => $user,
        ], Response::HTTP_OK, ["groups" => ["user:read"]]);
    }

    #[Route("/api/profile/", name: "app_api_profile_edit", requirements: ["id" => "\d+"], methods: ["PATCH"])]
    #[IsGranted("AUTHENTICATED_FULLY")]
    public function edit(#[CurrentUser] User $user, Request $request): Response
    {
        if($request->headers->get("Content-Type", "") !== "application/json" || $request->headers->get("Content-Type", "") !== "multipart/form-data") {
            return $this->json(["error" => "Request type invalid"], Response::HTTP_BAD_REQUEST);
        }

        $this->serializer->deserialize($request->getContent(), User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);

        $username = trim((string)$request->request->get("username") ?? "");
        $lastName = trim((string)$request->request->get("last_name") ?? "");
        $firstName = trim((string)$request->request->get("first_name") ?? "");

        $errors = [];

        if($username === "") $errors["name"] = "Username cannot be null.";
        if($lastName === "") $errors["last_name"] = "Last name cannot be null.";
        if($firstName === "") $errors["first_name"] = "First name cannot be null.";

        if($errors) {
            return $this->json([
                "errors" => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json([
            "data" => ["Profile updated successfully."],
        ], Response::HTTP_OK);
    }
}
