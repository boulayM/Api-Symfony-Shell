<?php

declare(strict_types=1);

namespace App\Dto\PublicApi;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Assert\Length(max: 120)]
    public ?string $lastName = null;
}