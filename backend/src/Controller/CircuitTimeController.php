<?php

namespace App\Controller;

use App\DTO\CircuitTimeDTO;
use App\Entity\Car;
use App\Entity\Circuit;
use App\Entity\CircuitTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CircuitTimeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer, private ValidatorInterface $validator)
    {
    }

    #[Route('/circuit/{id}/time', name: 'app_circuit_time')]
    public function index(#[CurrentUser] User $user, Circuit $circuit): Response
    {
        $circuitTimes = $this->em->getRepository(CircuitTime::class)->findBy(["circuit" => $circuit, "user" => $user]);

        return $this->json([
            $circuitTimes
        ], Response::HTTP_OK, [], ["groups" => "circuit-time:read"]);
    }

    #[Route("/api/circuit/{id}/time/create", name: "app_api_circuit_time_create", methods: ["POST"], requirements: ["id" => "\d+"])]
    public function create(Request $request, #[CurrentUser] User $user, #[MapEntity(mapping: ["id" => "id"])] Circuit $circuit): Response
    {
        $violations = new ConstraintViolationList();
        $dto = new CircuitTimeDTO();

        try {
            $dto = $this->serializer->deserialize($request->getContent(), CircuitTimeDTO::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $dto, DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS]);
        }catch(PartialDenormalizationException $e) {
            foreach($e->getNotNormalizableValueErrors() as $e) {
            $message = sprintf('The type must be one of "%s" (%s given)', implode(', ', $e->getExpectedTypes()), $e->getCurrentType());
            $parameters = [];
            if ($e->canUseMessageForUser()) {
                $parameters['hint'] = $e->getMessage();
            }
            $violations->add(new ConstraintViolation($message, '', $parameters, null, $e->getPath(), null));
            }
        }

        $violations->addAll($this->validator->validate($dto, null, ["circuit-time:create"]));

        if($violations->count() > 0) {
            $errors = [];

            foreach($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                "data" => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $circuitTime = new CircuitTime();

        if($dto->minutes !== null) {
            $circuitTime->setMinutes($dto->minutes);
        }

        if($dto->seconds !== null) {
            $circuitTime->setSeconds($dto->seconds);
        }

        $data = $request->toArray();
        $carId = isset($data["car"]) ? (int) $data["car"] : null;

        if(is_int($carId)) {
            $car = $this->em->getRepository(Car::class)->findOneBy(["id" => $carId]);

            $circuitTime->setUser($user)
                        ->setCircuit($circuit)
                        ->setCar($car);

            $this->em->persist($circuitTime);
            $this->em->flush();

            return $this->json([
                "data" => $car
            ], Response::HTTP_CREATED, [], ["groups" => ["car:read"]]);
        
        }else{
            return $this->json([
                "data" => [
                    "car" => "The car request is not set.(" . $carId . ")",
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route("/api/circuit-time/{id}/delete", name: "app_api_circuit_time_delete", methods: ["DELETE"], requirements: ["id" => "\d+"])]
    public function delete(Request $request, CircuitTime $circuitTime): Response
    {
        if(!$circuitTime instanceof CircuitTime) {
            return $this->json([
                "error" => [
                    "circuit_time" => "The circuit time record does not exist",
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->remove($circuitTime);
        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }
}
