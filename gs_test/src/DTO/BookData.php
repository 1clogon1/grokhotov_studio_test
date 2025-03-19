<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BookData
{
    /**
     * @Assert\NotBlank(message="Название книги не может быть пустым.")
     * @Assert\Length(max=255, maxMessage="Название книги не может превышать 255 символов.")
     */
    public $title;

    /**
     * @Assert\NotBlank(message="ISBN книги не может быть пустым.")
     * @Assert\Length(max=100, maxMessage="ISBN книги не может превышать 255 символов.")
     */
    public $isbn;

    /**
     * @Assert\NotBlank(message="Количество страниц не может быть пустым.")
     * @Assert\Type(type="integer", message="Количество страниц должно быть целым числом.")
     */
    public $pageCount;

    /**
     * @Assert\NotBlank(message="Дата публикации не может быть пустой.")
     * @Assert\Type(type="datetime", message="Дата публикации должна быть в формате datetime.")
     */
    public $publishedDate;

    /**
     * @Assert\Url(message="URL обложки должен быть корректным.")
     * @Assert\Length(max=255, maxMessage="URL обложки не может превышать 255 символов.")
     */
    public $thumbnailUrl;

    /**
     * @Assert\NotBlank(message="Краткое описание не может быть пустым.")
     */
    public $shortDescription;

    /**
     * @Assert\NotBlank(message="Длинное описание не может быть пустым.")
     */
    public $longDescription;

    /**
     * @Assert\NotBlank(message="Статус книги не может быть пустым.")
     * @Assert\Choice(choices={"available", "unavailable"}, message="Статус книги должен быть 'available' или 'unavailable'.")
     */
    public $status;

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