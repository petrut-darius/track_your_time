<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_api_registration', methods: ["POST"])]
    #[OA\Tag(name: "Authentication")]
    #[Oa\RequestBody(
        description: "User registration data",
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "email", type: "string", example: "user@example.com"),
                new OA\Property(property: 'username', type: 'string', example: 'john_doe'),                                                                                                                                             
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),                                                                                                                                               
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),                                                                                                                                                 
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Secret123456!') 
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: "User registered successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: 'email', type: 'string', example: 'user@example.com')
            ]
        )
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: "Validation error")]
    #[Security(name: null)]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, SerializerInterface $serializer, AuthenticationSuccessHandler $successHandler): JsonResponse
    {    
        try {
            $data = $request->getContent();
            json_decode($data, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(["error" => "The request body must be valid JSON"], Response::HTTP_BAD_REQUEST);
        }

        $violations = new ConstraintViolationList();
        $user = new User();

        try {
            $serializer->deserialize($data, User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user, DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, AbstractNormalizer::IGNORED_ATTRIBUTES => ["id", "password", "roles"]]);
        }catch(PartialDenormalizationException $e) {
            //tot ce aici adauga in $vioaltions erori in mare parte de tipul variabilei
            foreach ($e->getNotNormalizableValueErrors() as $e) {
                $message = sprintf('The type must be one of "%s" (%s given)', implode(', ', $e->getExpectedTypes()), $e->getCurrentType());
                $parameters = [];
                if ($e->canUseMessageForUser()) {
                    $parameters['hint'] = $e->getMessage();
                }
                $violations->add(new ConstraintViolation($message, '', $parameters, null, $e->getPath(), null));
            }
        }

        $violations->addAll($validator->validate($user, null, ["registration"]));//asta adauga efectiv de lungime etc($validatoru e cel care verifica alea de is setate in entitate)

        $errors = [];

        if($violations->count() > 0) {
            foreach($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                "errors" => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

    $plainPassword = $user->getPlainPassword();
    if ($plainPassword === null) {
        // belt-and-suspenders: should be caught by validation above, but never trust one layer
        return $this->json(["errors" => ["plainPassword" => "This value should not be blank."]], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->setPlainPassword(null);
        $user->setRoles(["ROLE_USER"]);

        $em->persist($user);
        $em->flush();

        return $successHandler->handleAuthenticationSuccess($user);
    }
}
