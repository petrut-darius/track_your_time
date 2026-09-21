<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmailConstraint extends Constraint
{
    public string $message = "The email is already taken.";
}