<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $isbn;

    #[ORM\Column(type: 'integer')]
    private int $page_count;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $published_date;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $thumbnail_url;

    #[ORM\Column(type: 'text')]
    private string $short_description;

    #[ORM\Column(type: 'text')]
    private string $long_description;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_authors')]
    private $authors;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_categories')]
    private $categories;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getPageCount(): int
    {
        return $this->page_count;
    }

    public function setPageCount(int $pageCount): self
    {
        $this->page_count = $pageCount;
        return $this;
    }

    public function getPublishedDate(): \DateTimeInterface
    {
        return $this->published_date;
    }

    public function setPublishedDate(\DateTimeInterface $publishedDate): self
    {
        $this->published_date = $publishedDate;
        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnail_url = $thumbnailUrl;
        return $this;
    }

    public function getShortDescription(): string
    {
        return $this->short_description;
    }

    public function setShortDescription(string $shortDescription): self
    {
        $this->short_description = $shortDescription;
        return $this;
    }

    public function getLongDescription(): string
    {
        return $this->long_description;
    }

    public function setLongDescription(string $longDescription): self
    {
        $this->long_description = $longDescription;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAuthors()
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): self
    {
        if (!$this->authors->contains($author)) {
            $this->authors[] = $author;
        }
        return $this;
    }

    public function removeAuthor(Author $author): self
    {
        $this->authors->removeElement($author);
        return $this;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }
        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);
        return $this;
    }
}

