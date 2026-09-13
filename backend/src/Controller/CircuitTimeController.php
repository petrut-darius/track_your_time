<?php

namespace App\Controller;

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

final class CircuitTimeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/circuit/{id}/time', name: 'app_circuit_time')]
    public function index(#[CurrentUser] User $user, Circuit $circuit): Response
    {
        if(!$circuit instanceof Circuit) {
            return $this->json([]);
        }

        $circuitTimes = $this->em->getRepository(CircuitTime::class)->findBy(["circuit" => $circuit, "user" => $user]);

        return $this->json([
            $circuitTimes
        ], Response::HTTP_OK, [], ["groups" => "circuit-time:read"]);
    }

    #[Route("/api/circuit/{id}/time/create", name: "app_api_circuit_time_create", methods: ["POST"], requirements: ["id" => "\d+"])]
    public function create(Request $request, #[CurrentUser] User $user, #[MapEntity(mapping: ["id" => "id"])] Circuit $circuit): Response
    {
        //only json

        if(!$circuit instanceof Circuit) {
            return $this->json([
                "error" => [
                    "circuit" => "The circuit does not exist",
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->toArray();

        $carName = $data["car_name"];

        if(!$this->em->getRepository(Car::class)->findOneBy(["name" => $carName])) {
            return $this->json([
                "error" => [
                    "car" => "The car does not exist",
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $car = $this->em->getRepository(Car::class)->findOneBy(["name" => $carName]);
        $time = \DateTime::createFromFormat("H:i:s", $data["time"]);

        if($time === false) {
            return $this->json([
                "error" => [
                    "car" => "The time does not exist",
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $circuitTime = new CircuitTime();
        $circuitTime->setUser($user)
                    ->setCircuit($circuit)
                    ->setTime($time);
                    //setcar

        $this->em->persist($circuitTime);
        $this->em->flush();

        return $this->json([], Response::HTTP_CREATED);
    }

    //must redo can't pass id in front-end
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
