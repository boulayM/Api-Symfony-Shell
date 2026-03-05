<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 120)]
    public string $password = '';

    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Assert\Length(max: 120)]
    public ?string $lastName = null;
}