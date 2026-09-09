<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FileUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer)
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
    public function edit(#[CurrentUser] User $user, Request $request, ValidatorInterface $validator, #[Autowire(service: "App\Service\FileUploaderService.profile")] FileUploaderService $avatarUploader, Filesystem $fileSystem, LoggerInterface $logger): Response
    {
        if($request->headers->get("Content-Type", "") !== "application/json" || $request->headers->get("Content-Type", "") !== "multipart/form-data") {
            return $this->json(["error" => "Request type invalid"], Response::HTTP_BAD_REQUEST);
        }

        if($request->files->count() === 0) {
            $violations = new ConstraintViolationList();

            try {
                $this->serializer->deserialize($request->getContent(), User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user, DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true]);
            }catch(PartialDenormalizationException $e) {
                foreach ($e->getNotNormalizableValueErrors() as $e) {
                    $message = sprintf('The type must be one of "%s" (%s given)', implode(', ', $e->getExpectedTypes()), $e->getCurrentType());
                    $parameters = [];
                    if ($e->canUseMessageForUser()) {
                        $parameters['hint'] = $e->getMessage();
                    }
                    $violations->add(new ConstraintViolation($message, '', $parameters, null, $e->getPath(), null));
                }
            }

            $violations->addAll($validator->validate($user));

            $errors = [];

            if($violations->count() > 0) {
                foreach($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                return $this->json([
                    "errors" => $errors,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

        }else{
            $email = filter_var(trim((string)$request->request->get("email") ?? ""), FILTER_SANITIZE_EMAIL);
            $username = trim((string)$request->request->get("username") ?? "");
            $lastName = trim((string)$request->request->get("last_name") ?? "");
            $firstName = trim((string)$request->request->get("first_name") ?? "");

            $errors = [];

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors["email"] = "Email is not a valid email value.";
            if($email === "") $errors["email"] = "Email can't be null";
            if($username === "") $errors["name"] = "Username cannot be null.";
            if($lastName === "") $errors["last_name"] = "Last name cannot be null.";
            if($firstName === "") $errors["first_name"] = "First name cannot be null.";

            if($errors) {
                return $this->json([
                    "errors" => $errors
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            $user->setUsername($username);
            $user->setLastName($lastName);
            $user->setFirstName($firstName);
        }

        //add photo to user
        $avatar = is_array($request->request->get("avatar")) ? $request->request->get("avatar") : [$request->request->get("avatar")];
        if($avatar && $avatar instanceof UploadedFile) {
            $oldAvatar =(string) $user->getAvatar();

            if($oldAvatar) {
                try {
                    $fileSystem->remove($oldAvatar);
                }catch(FileException $e) {
                    $logger->error("The photo($oldAvatar) hasn't been deleted on the update of the location image.", [
                        "line" => $e->getLine(),
                        "exception_code" => $e->getCode(),
                        "error" => $e->getMessage(),
                        "file" => $oldAvatar,
                    ]);

                    return $this->json([
                        "data" => "Some error occured.",
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }


            $avatarName =(array) $avatarUploader->upload($avatar);
            $user->setAvatar($avatarName);
        }

        $this->em->flush();

        return $this->json([
            "data" => ["Profile updated successfully."],
        ], Response::HTTP_OK);
    }
}
