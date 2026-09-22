<?php

namespace App\Controller;

use App\DTO\CarDTO;
use App\Entity\Car;
use App\Entity\User;
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
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CarController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, #[Autowire(service: "App\Service\FileUploaderService.cars")] private FileUploaderService $fileUploader, private LoggerInterface $logger, private SerializerInterface $serializer, private ValidatorInterface $validator)
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
        $violations = new ConstraintViolationList();
        $dto = new CarDTO();

        if($request->files->count() === 0) {
            try {
                $dto = $this->serializer->deserialize($request->getContent(), CarDTO::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $dto, DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, AbstractNormalizer::IGNORED_ATTRIBUTES => ["id", "user"]]);
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
        }else{
            $dto->name = trim((string) $request->request->get("name"));
            $dto->hp = (int) $request->request->get("hp");
            $dto->story = trim((string) $request->request->get("story"));
            $dto->photos = $request->files->all("photos");
        }

        $violations->addAll($this->validator->validate($dto, null, ["car:create"]));

        if($violations->count() > 0) {
            $errors = [];

            foreach($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json([
                "data" => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $car = new Car;

        if($dto->photos) {
            $photoNames = [];
            $fileList = is_array($dto->photos) ? $dto->photos : [$dto->photos];

            foreach($fileList as $file) {
                if($file instanceof UploadedFile) {
                    $photoNames[] = $this->fileUploader->upload($file);
                }
            }

            $car->setPhotos($photoNames);
        }

        if($dto->name !== null) {
            $car->setName($dto->name);
        }

        if($dto->hp !== null && is_numeric($dto->hp)) {
            $car->setHp($dto->hp);
        }

        if($dto->story !== null) {
            $car->setStory($dto->story);
        }

        $car->setUser($user);

        $this->em->persist($car);
        $this->em->flush();

        return $this->json([
            "data" => $car,
        ], Response::HTTP_CREATED, [], ["groups" => ["car:read"]]);
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
            "data" => $car,
        ], Response::HTTP_OK, [], ["groups" => "car:read"]);
    }

    #[Route("/api/cars/{id}/edit", name: "app_api_car_edit", methods: [ "PATCH"], requirements: ["id" => "\d+"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
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
        $violations = new ConstraintViolationList();
        $dto = new CarDTO();

        if($request->files->count() === 0) {
            try{
                $dto = $this->serializer->deserialize($request->getContent(), CarDTO::class, "json", [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, "groups" => ["car:update"]]);
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
        }else {
            $name = $request->request->get("name"); 
            $dto->name = $name === null ? null : trim($name);

            $hp = $request->request->get("hp");
            $dto->hp = $hp === null ? null : (int) $hp;

            $story = $request->request->get("story");
            $dto->story = $story === null ? null : trim($story);
            
            $dto->photos = $request->files->get("photos") ?? null;
        }

        $violations->addAll($this->validator->validate($dto, null, ["car:update"]));

        $hasChanges = false;

        if($dto->name !== null && $dto->name !== $car->getName()) {
            $car->setName($dto->name);
            $hasChanges = true;
        }

        if($dto->story !== null && $dto->story !== $car->getStory()) {
            $car->setStory($dto->story);
            $hasChanges = true;
        }

        if($dto->hp !== null && $dto->hp !== $car->getHp()) {
            $car->setHp($dto->hp);
            $hasChanges = true;
        }

        if($hasChanges) {
            $this->em->flush();
        }

        $photoNames = [];

        if($dto->photos) {
            $oldPhotos = $car->getPhotos();

            foreach($dto->photos as $photo) {
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
            "data" => $car,
        ], Response::HTTP_OK, [], ["groups" => ["car:read"]]);
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
