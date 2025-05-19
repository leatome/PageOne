<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserBookCollection;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 512)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $htmlUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $epubUrl = null;

    #[ORM\Column(type: 'json')]
    private array $subjects = [];

    #[ORM\Column(type: 'json')]
    private array $bookshelves = [];

    #[ORM\Column]
    private int $downloadCount = 0;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'books', cascade: ['persist'])]
    private Collection $categories;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'book')]
    private Collection $comments;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'book')]
    private Collection $ratings;

    /**
     * @var Collection<int, UserBookCollection>
     */
    #[ORM\OneToMany(targetEntity: UserBookCollection::class, mappedBy: 'book')]
    private Collection $userBookCollections;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->userBookCollections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): static
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): static
    {
        $this->htmlUrl = $htmlUrl;

        return $this;
    }

    public function getEpubUrl(): ?string
    {
        return $this->epubUrl;
    }

    public function setEpubUrl(?string $epubUrl): static
    {
        $this->epubUrl = $epubUrl;

        return $this;
    }

    public function getSubjects(): array
    {
        return $this->subjects;
    }

    public function setSubjects(array $subjects): static
    {
        $this->subjects = $subjects;

        return $this;
    }

    public function getBookshelves(): array
    {
        return $this->bookshelves;
    }

    public function setBookshelves(array $bookshelves): static
    {
        $this->bookshelves = $bookshelves;

        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(int $downloadCount): static
    {
        $this->downloadCount = $downloadCount;

        return $this;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addBook($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeBook($this);
        }

        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setBook($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getBook() === $this) {
                $comment->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function getAverageRating(): ?float
    {
        if ($this->ratings->isEmpty()) {
            return null;
        }

        $sum = array_reduce($this->ratings->toArray(), fn($carry, $rating) => $carry + $rating->getRating(), 0);
        return round($sum / count($this->ratings), 1);
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setBook($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            if ($rating->getBook() === $this) {
                $rating->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserBookCollection>
     */
    public function getUserBookCollections(): Collection
    {
        return $this->userBookCollections;
    }

    public function addUserBookCollection(UserBookCollection $userBookCollection): static
    {
        if (!$this->userBookCollections->contains($userBookCollection)) {
            $this->userBookCollections->add($userBookCollection);
            $userBookCollection->setBook($this);
        }

        return $this;
    }

    public function removeUserBookCollection(UserBookCollection $userBookCollection): static
    {
        if ($this->userBookCollections->removeElement($userBookCollection)) {
            // set the owning side to null (unless already changed)
            if ($userBookCollection->getBook() === $this) {
                $userBookCollection->setBook(null);
            }
        }

        return $this;
    }
}
