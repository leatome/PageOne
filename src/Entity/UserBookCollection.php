<?php

namespace App\Entity;

use App\Repository\UserBookCollectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBookCollectionRepository::class)]
#[ORM\UniqueConstraint(fields: ['user', 'book'])]
class UserBookCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userCollection = null;

    #[ORM\ManyToOne(inversedBy: 'userBookCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserCollection(): ?User
    {
        return $this->userCollection;
    }

    public function setUserCollection(?User $userCollection): static
    {
        $this->userCollection = $userCollection;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }
}
