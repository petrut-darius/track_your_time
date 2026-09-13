<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AuthenticationSuccessListener
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    #[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
    public function onLexikJwtAuthenticationOnAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if(!$user instanceof User) {
            return;
        }

        $existingData = $event->getData();

        $userData = $this->normalizer->normalize($user, null, [AbstractNormalizer::GROUPS => ["user:read"]]);

        $existingData["data"] = $userData;

        $event->setData($existingData);
    }
}
