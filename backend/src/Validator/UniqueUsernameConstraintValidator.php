<?php

namespace App\Validator;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueUsernameConstraintValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em, private Security $security)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUsernameConstraint) {
            throw new UnexpectedTypeException($constraint, UniqueUsernameConstraint::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        $existing = $this->em->getRepository(User::class)->findOneBy(["username" => $value]);

        if($existing !== null && $existing->getId() !== $currentUser->getId()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}