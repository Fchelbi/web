<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->likedByUsers = new ArrayCollection();
        $this->dislikedByUsers = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $likes = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dislikes = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true, cascade: ['persist'])]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'post_likes')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id_user')]
    private Collection $likedByUsers;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'post_dislikes')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id_user')]
    private Collection $dislikedByUsers;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFlagged = false;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $flagReason = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'approved'])]
    private string $moderationStatus = 'approved';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): self
    {
        $this->likes = $likes;
        return $this;
    }

    public function getDislikes(): ?int
    {
        return $this->dislikes;
    }

    public function setDislikes(?int $dislikes): self
    {
        $this->dislikes = $dislikes;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    public function setIsFlagged(bool $isFlagged): self
    {
        $this->isFlagged = $isFlagged;
        return $this;
    }

    public function getFlagReason(): ?string
    {
        return $this->flagReason;
    }

    public function setFlagReason(?string $flagReason): self
    {
        $this->flagReason = $flagReason;
        return $this;
    }

    public function getModerationStatus(): string
    {
        return $this->moderationStatus;
    }

    public function setModerationStatus(string $moderationStatus): self
    {
        $this->moderationStatus = $moderationStatus;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getLikedByUsers(): Collection
    {
        return $this->likedByUsers;
    }

    public function addLikedByUser(User $user): self
    {
        if (!$this->likedByUsers->contains($user)) {
            $this->likedByUsers[] = $user;
        }
        return $this;
    }

    public function removeLikedByUser(User $user): self
    {
        $this->likedByUsers->removeElement($user);
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getDislikedByUsers(): Collection
    {
        return $this->dislikedByUsers;
    }

    public function addDislikedByUser(User $user): self
    {
        if (!$this->dislikedByUsers->contains($user)) {
            $this->dislikedByUsers[] = $user;
        }
        return $this;
    }

    public function removeDislikedByUser(User $user): self
    {
        $this->dislikedByUsers->removeElement($user);
        return $this;
    }
}
