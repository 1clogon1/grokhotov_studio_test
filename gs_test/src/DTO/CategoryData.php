<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryData
{
    #[Assert\NotBlank(message: 'Название категории не может быть пустым.')]
    #[Assert\Length(max: 255, maxMessage: 'Название категории не может превышать 255 символов.')]
    public ?string $name = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
