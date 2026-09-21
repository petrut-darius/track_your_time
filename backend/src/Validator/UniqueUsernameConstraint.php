<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUsernameConstraint extends Constraint
{
    public string $message = "The username is already taken.";
}