<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
    ) {
    }

    #[Route('/api/notifications', name: 'app_api_notifications_list', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Response(response: Response::HTTP_OK, description: 'Get notifications list and unread count for current user')]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $notifications = $this->em->getRepository(Notification::class)->findBy(
            ['recepient' => $user],
            ['id' => 'DESC'],
            50
        );

        $unreadCount = $this->em->getRepository(Notification::class)->count([
            'recepient' => $user,
            'readAt' => null,
        ]);

        return $this->json([
            'data' => $notifications,
            'unreadCount' => $unreadCount,
        ], Response::HTTP_OK, [], ['groups' => 'notifications:read']);
    }

    #[Route('/api/notifications/subscribe', name: 'app_api_notifications_subscribe', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Response(response: Response::HTTP_OK, description: 'Get Mercure subscription info and access token')]
    public function subscribe(#[CurrentUser] User $user): JsonResponse
    {
        $topic = sprintf(
            'https://track-your-time.ddev.site/users/%d/notifications',
            $user->getId()
        );

        $factory = $this->hub->getFactory();
        if (!$factory) {
            return $this->json(['error' => 'Token factory not available'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = $factory->create(
            [new Grant([Grant::ACTION_SUBSCRIBE], [$topic])],
            ['aud' => [
                'http://mercure.track-your-time.ddev.site:8444/.well-known/mercure',
                'https://mercure.track-your-time.ddev.site:8444/.well-known/mercure',
            ]]
        );

        $cookieName = $this->hub->getCookieName();
        $response = $this->json([
            'hubUrl' => $this->hub->getPublicUrl(),
            'topic' => $topic,
            'token' => $token,
        ]);

        $cookie = Cookie::create($cookieName)
            ->withValue($token)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_NONE);

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/notifications/{id}/read', name: 'app_api_notifications_mark_read', methods: ['PATCH'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Response(response: Response::HTTP_OK, description: 'Mark a single notification as read')]
    public function markAsRead(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $notification = $this->em->getRepository(Notification::class)->find($id);

        if (!$notification || $notification->getRecepient()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if (null === $notification->getReadAt()) {
            $notification->setReadAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        return $this->json([
            'data' => $notification,
        ], Response::HTTP_OK, [], ['groups' => 'notifications:read']);
    }

    #[Route('/api/notifications/read-all', name: 'app_api_notifications_read_all', methods: ['POST'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Response(response: Response::HTTP_OK, description: 'Mark all unread notifications as read')]
    public function markAllAsRead(#[CurrentUser] User $user): JsonResponse
    {
        $updatedCount = $this->em->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.readAt', ':now')
            ->where('n.recepient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $this->json([
            'success' => true,
            'updatedCount' => $updatedCount,
        ]);
    }
}
