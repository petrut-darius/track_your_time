<?php

namespace App\Controller;

use App\DTO\ProfileUpdateDTO;
use App\Entity\User;
use App\Repository\FriendshipRepository;
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
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer, private FriendshipRepository $friendshipRepository)
    {
    }

    #[Route('/api/user/{id}', name: 'app_api_profile', requirements: ["id" => "\d+"], methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(int $id, #[CurrentUser] User $currentUser): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(["id" => $id]);

        if($user === null) {
            return $this->json(["error" => "User not found."], Response::HTTP_NOT_FOUND);
        }

        $friendShipStatus = null;
        if($user !== $currentUser) {
            $friendship = $this->friendshipRepository->findBetween($currentUser, $user);
            $friendShipStatus = $friendship?->getStatus();    
        }

        $userData = json_decode($this->serializer->serialize($user, "json", ["groups" => ["user:read", "friendship:read"]]), true);
        $userData["friendship_status"] = $friendShipStatus;

        return $this->json([
            "data" => $userData,
        ], Response::HTTP_OK);
    }

    #[Route("/api/profile", name: "app_api_profile_get", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getEditInfo(#[CurrentUser] User $user): Response
    {
        return $this->json([
            "data" => $user
        ], Response::HTTP_OK, [], ["groups" => ["user:update"]]);
    }

    #[Route("/api/profile", name: "app_api_profile_edit", methods: ["PATCH"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function edit(#[CurrentUser] User $user, Request $request, ValidatorInterface $validator, #[Autowire(service: "App\Service\FileUploaderService.profile")] FileUploaderService $avatarUploader, Filesystem $fileSystem, LoggerInterface $logger): Response
    {
        //check for json or form-data


        $violations = new ConstraintViolationList();
        $dto = new ProfileUpdateDTO();

        if($request->files->count() === 0) {

            try {
                $dto = $this->serializer->deserialize($request->getContent(), ProfileUpdateDTO::class, "json", [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, "groups" => ["user:update"]]);
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
        }else{
            $dto->email = trim((string) $request->request->get("email"));
            $dto->username = trim((string) $request->request->get("username"));
            $dto->firstName = trim((string) $request->request->get("first_name"));
            $dto->lastName = trim((string) $request->request->get("last_name"));
            $dto->avatar = $request->files->get("avatar");    
        }

        $violations->addAll($validator->validate($dto, null, ["user:update"]));

        if($violations->count() > 0) {
            $errors = [];

            foreach($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                "data" => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $hasChanges = false;

        if ($dto->email !== $user->getEmail() && $dto->email !== null) {
            $user->setEmail($dto->email);
            $hasChanges = true;
        }

        if ($dto->username !== $user->getUsername()  && $dto->username !== null) {
            $user->setUsername($dto->username);
            $hasChanges = true;
        }

        if ($dto->firstName !== $user->getFirstName() && $dto->firstName !== null) {
            $user->setFirstName($dto->firstName);
            $hasChanges = true;
        }

        if ($dto->lastName !== $user->getLastName() && $dto->lastName !== null) {
            $user->setLastName($dto->lastName);
            $hasChanges = true;
        }

        if($hasChanges) {
            $this->em->flush();
        }

        if($dto->avatar instanceof UploadedFile) {
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
                }
            }

            $avatarName =(string) $avatarUploader->upload($dto->avatar);
            $user->setAvatar($avatarName);   
            $this->em->flush();
        }

        return $this->json([
            "data" => $user
        ], Response::HTTP_OK);
    }
}
