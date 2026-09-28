<?php

namespace App\Security\Voter;

use App\Entity\Car;
use App\Entity\Circuit;
use App\Entity\CircuitTime;
use App\Entity\Friendship;
use App\Entity\Grade;
use App\Entity\Manufacture;
use App\Entity\Model;
use App\Entity\Status;
use App\Entity\Type;
use App\Entity\User;
use App\Service\CircuitService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdminVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if($subject === null) {
            return true;
        }

        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW])
            && in_array(get_class($subject), [Manufacture::class, Car::class, CircuitTime::class, Friendship::class, User::class, Type::class, Status::class, Grade::class, Model::class, Circuit::class]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        $userRoles = $user->getRoles();

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                // logic to determine if the user can EDIT
                // return true or false
                if($subject instanceof User) {
                    if(in_array("ROLE_SUPER_ADMIN", $userRoles, true)) {
                        return true;
                    }

                    if(in_array("ROLE_ADMIN", $userRoles, true)) {
                        return !in_array("ROLE_ADMIN", $subject->getRoles(), true) && !in_array("ROLE_SUPER_ADMIN", $subject->getRoles(), true);
                        //!false && !true => false; !false && !false => true, !true && !false => false, !true && !true => false
                    }

                    return false;
                }

                if(in_array("ROLE_SUPER_ADMIN", $userRoles, true) || in_array("ROLE_ADMIN", $userRoles, true)) return true;

                return false;
            case self::VIEW:
                // logic to determine if the user can VIEW
                // return true or false
                if(in_array("ROLE_ADMIN", $userRoles, true) || in_array("ROLE_SUPER_ADMIN", $userRoles, true)) return true;
                return false;
        }

        return false;
    }
}
