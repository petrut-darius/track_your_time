<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Service\FileUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CarController extends AbstractController
{
    

    public function __construct(private EntityManagerInterface $em, private FileUploaderService $fileUploader, private LoggerInterface $logger)
    {
    }

    #[Route('/api/cars', name: 'app_api_car', methods: ["GET", "POST"])] //post for the filters or maybe make a specific controller for that
    public function index(): Response
    {
        $cars = $this->em->getRepository(CarRepository::class)->findAll();

        return $this->json([
            "data" => $cars,
        ], Response::HTTP_OK);
    }

    #[Route("/api/cars/create", name: "app_api_car_create", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $name = trim((string) ($request->request->get("name") ?? ""));
        $hp = trim((int) ($request->request->getInt("hp") ?? 0));
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
        if($story === 0) $errors["story"] = "Your cars story is required."; 

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
    public function show(?Car $car): Response
    {
        if(!$car instanceof Car) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            "data" => $car,
        ], Response::HTTP_OK, [], ["groups" => "car:read"]);
    }

    #[Route("/api/cars/{id}/edit", name: "app_api_car_edit", methods: [ "POST"], requirements: ["id" => "\d+"])]
    #[IsGranted("AUTHENTICATED_FULLY")]
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
            }

            if($oldPhotos) {
                try {
                    foreach($oldPhotos as $oldPhoto) {
                        $oldPhotoPath = $this->getParameter("car_photos_directory") . $oldPhoto;
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
                        ]);
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

        $this->em->remove($car);
        $this->em->flush();

        return $this->json([
            "data" => "Successfully deleted your car.",
        ], Response::HTTP_OK);
    }
}
