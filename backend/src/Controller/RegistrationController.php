<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_api_registration', methods: ["POST"])]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): JsonResponse
    {    
        try {
            $data = $request->toArray();
        } catch (\JsonException $e) {
            return $this->json(["error" => "The request body must be valid JSON"], Response::HTTP_BAD_REQUEST);
        }

        $email = trim((string) ($data["email"] ?? ""));
        $username = trim((string) ($data["username"] ?? ""));
        $last_name = trim((string) ($data["last_name"] ?? ""));
        $first_name = trim((string) ($data["first_name"] ?? ""));
        $plainTextPassword = trim((string) ($data["password"] ?? ""));

        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Enter a valid email address.";
        } else if ($userRepository->findOneBy(["email" => $email])) {
            $errors["email"] = "An account with this email already exists.";
        }

        if (mb_strlen($plainTextPassword) < 8) {
            $errors["password"] = "Password must contain at least 12 characters.";
        }

        if($username === "") $errors["username"] = "Username is required.";
        if($first_name === "") $errors["first_name"] = "First name is required.";
        if($last_name === "") $errors["last_name"] = "Last name is required.";

        if ($errors) {
            return $this->json(["errors" => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();    

        $hashedPassword = $passwordHasher->hashPassword($user, $plainTextPassword);

        $user->setEmail(mb_strtolower($email, "UTF-8"))
            ->setUsername($username)
            ->setLastName($last_name)
            ->setFirstName($first_name)
            ->setPassword($passwordHasher->hashPassword($user, $plainTextPassword));

        $em->persist($user);
        $em->flush();

        return $this->json([
            "id" => $user->getId(),
            "email" => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }
}
