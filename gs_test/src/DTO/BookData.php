<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BookData
{
    #[Assert\NotBlank(message: 'Название книги не может быть пустым.')]
    #[Assert\Length(max: 255, maxMessage: 'Название книги не может превышать 255 символов.')]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'ISBN книги не может быть пустым.')]
    #[Assert\Length(max: 100, maxMessage: 'ISBN книги не может превышать 100 символов.')]
    public ?string $isbn = null;

    #[Assert\NotBlank(message: 'Количество страниц не может быть пустым.')]
    #[Assert\Type(type: 'integer', message: 'Количество страниц должно быть целым числом.')]
    public ?int $pageCount = null;

    #[Assert\NotBlank(message: 'Дата публикации не может быть пустой.')]
    #[Assert\Type(type: '\DateTimeInterface', message: 'Дата публикации должна быть в формате datetime.')]
    public ?\DateTimeInterface $publishedDate = null;

    #[Assert\Url(message: 'URL обложки должен быть корректным.')]
    #[Assert\Length(max: 255, maxMessage: 'URL обложки не может превышать 255 символов.')]
    public ?string $thumbnailUrl = null;

    #[Assert\NotBlank(message: 'Краткое описание не может быть пустым.')]
    public ?string $shortDescription = null;

    #[Assert\NotBlank(message: 'Длинное описание не может быть пустым.')]
    public ?string $longDescription = null;

    #[Assert\NotBlank(message: 'Статус книги не может быть пустым.')]
    #[Assert\Choice(choices: ['available', 'unavailable'], message: "Статус книги должен быть 'available' или 'unavailable'.")]
    public ?string $status = null;

    public function __construct(
        ?string $title = null,
        ?string $isbn = null,
        ?int $pageCount = null,
        ?\DateTimeInterface $publishedDate = null,
        ?string $thumbnailUrl = null,
        ?string $shortDescription = null,
        ?string $longDescription = null,
        ?string $status = null
    ) {
        $this->title = $title;
        $this->isbn = $isbn;
        $this->pageCount = $pageCount;
        $this->publishedDate = $publishedDate;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->status = $status;
    }
}
