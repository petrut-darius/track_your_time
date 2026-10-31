<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\LiveNotificationMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsMessageHandler]
final class LiveNotificationMessageHandler{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $em, private HubInterface $hub)
    {
    }

    public function __invoke(LiveNotificationMessage $message): void
    {
        $recepient = $this->userRepository->findOneBy(["id" => $message->getRecepientId()]);

        $notification = new Notification();

        $notification->setContent($message->getContent());
        $notification->setRecepient($recepient);
        $notification->setType($message->getType());
        $notification->setTargetId($message->getTargetId());

        $this->em->persist($notification);
        $this->em->flush();

        $topic = sprintf(
            'https://track-your-time.ddev.site/users/%d/notifications',
            $recepient->getId()
        );

        $payload = json_encode([
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'content' => $notification->getContent(),
            'targetId' => $notification->getTargetId(),
            'readAt' => null,
        ], JSON_THROW_ON_ERROR);

        $update = new Update(
            $topic,
            $payload,
            true, // Private update
        );

        $this->hub->publish($update);
    }
}
