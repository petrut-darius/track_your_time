<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\FileUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
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
use OpenApi\Attributes as OA;

final class CarController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, #[Autowire(service: "App\Service\FileUploaderService.cars")] private FileUploaderService $fileUploader, private LoggerInterface $logger)
    {
    }

    #[Route('/api/cars', name: 'app_api_car', methods: ["GET", "POST"])] //post for the filters or maybe make a specific controller for that
    #[OA\Tag(name: "Cars")]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "List of all the cars in the db",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Car::class))
        )
    )]
    public function index(): Response
    {
        $cars = $this->em->getRepository(Car::class)->findAll();

        return $this->json([
            $cars,
        ], Response::HTTP_OK);
    }

    #[Route("/api/cars/create", name: "app_api_car_create", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[OA\Tag(name: "Cars")]
    #[OA\RequestBody(
        description: "Car registration data",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["name", "hp", "story"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "hp", type: "integer"),
                    new OA\Property(property: "story", type: "string"),
                    new OA\Property(property: "photos", type: "array", items: new OA\Items(type: "string", format: "binary"))
                ],
                type: "object",
            )
        )
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Successfully created your car",
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: "Validation errors"
    )]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $name = trim((string) ($request->request->get("name") ?? ""));
        $hp = (int) ($request->request->getInt("hp") ?? 0);
        $story = trim((string) ($request->request->get("story") ?? ""));

        $photos = $request->files->get("photos");
        $photoNames = [];

        if($photos) {
            $fileList = is_array($photos) ? $photos : [$photos];

            foreach($fileList as $file) {
                if($file instanceof UploadedFile) {
                    $photoNames[] = $this->fileUploader->upload($file);
                }
            }
        }

        $errors = [];

        if($name === "") $errors["name"] = "Your cars slug is required.";
        if($hp === 0) $errors["hp"] = "Your car horsepower is required.";
        if($story === "") $errors["story"] = "Your cars story is required."; 

        if($errors) {
            return $this->json([
                "errors" => $errors
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $car = new Car();

        $car->setName($name)
            ->setHp($hp)
            ->setStory($story)
            ->setPhotos($photoNames)
            ->setUser($user);

        $this->em->persist($car);
        $this->em->flush();

        return $this->json([
            "data" => "Successfully created your car",
        ], Response::HTTP_CREATED);
    }

    #[Route("/api/cars/{id}", name: "app_api_car_show", methods: ["GET"], requirements: ["id" => "\d+"])]
    #[OA\Tag(name: "Cars")]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Data for the show page of a car",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Car::class))
        )
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: "Car entity does not exist")]
    public function show(?Car $car): Response
    {
        if(!$car instanceof Car) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            $car,
        ], Response::HTTP_OK, [], ["groups" => "car:read"]);
    }

    #[Route("/api/cars/{id}/edit", name: "app_api_car_edit", methods: [ "PATCH"], requirements: ["id" => "\d+"])]
    #[IsGranted("AUTHENTICATED_FULLY")]
    #[OA\Tag(name: "Cars")]
    #[OA\RequestBody(
        description: "Car update data",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "hp", type: "integer"),
                    new OA\Property(property: "story", type: "string"),
                    new OA\Property(property: "photos", type: "array", items: new OA\Items(type: "string", format: "binary"))
                ],
                type: "object",
            )
        )
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Successfully updated your car",
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: "Validation errors"
    )]
    public function edit(?Car $car, Request $request, Filesystem $fileSystem): Response
    {
        if(!$car instanceof Car) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $oldPhotos = $car->getPhotos();

        $photos = is_array($request->request->get("photos")) ? $request->request->get("photos") : [$request->request->get("photos")];
        $photoNames = [];

        if($photos) {

            foreach($photos as $photo) {
                if($photo instanceof UploadedFile) {
                    $photoNames[] = $this->fileUploader->upload($photo);
                }

                $car->setPhotos($photoNames);
                $this->em->flush();
            }

            if($oldPhotos) {
                try {
                    foreach($oldPhotos as $oldPhoto) {
                        $oldPhotoPath = $this->getParameter("car_photos_directory") . "/" . $oldPhoto;
                        $fileSystem->remove($oldPhotoPath);
                    }
                }catch(FileException $e) {
                        $this->logger->error("The photo($oldPhoto) hasn't been deleted on the update of the location image.", [
                            "line" => $e->getLine(),
                            "exception_code" => $e->getCode(),
                            "error" => $e->getMessage(),
                            "file" => $oldPhoto,
                        ]);

                        return $this->json([
                            "data" => "Some error occured.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

        }

        return $this->json([
            "data" => "Successfully updated your car",
        ], Response::HTTP_OK);
    }

    #[Route("/api/cars/{id}/delete", name: "app_api_car_delete", requirements: ["id" => "\d+"], methods: ["DELETE"])]
    #[IsGranted("AUTHENTICATED_FULLY")]
    public function delete(Request $request, ?Car $car, #[CurrentUser] User $user): Response
    {
        if(!$car instanceof Car) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        //delete photos from disk

        $this->em->remove($car);
        $this->em->flush();

        return $this->json([
            "data" => "Successfully deleted your car.",
        ], Response::HTTP_OK);
    }
}
