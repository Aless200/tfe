<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Serializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Veuillez entrer votre email.')]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
        message: 'L\'email n\'est pas valide.',
    )]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Veuillez entrer votre prénom.')]
    #[Assert\Length(
        min: 3,
        max: 120,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\regex(
        pattern: "/^[A-Za-zÀÂÄÉÈÊËÎÏÔÖÙÛÜÇàâäéèêëîïôöùûüç][a-zàâäéèêëîïôöùûüç\-']+$/",
        message:'Le prénom n\'est pas valide.'
    )]
    #[ORM\Column(length: 120)]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'Veuillez entrer votre nom.')]
    #[Assert\Length(
        min: 3,
        max: 120,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.',
    )]
    #[Assert\regex(
        pattern: "/^[A-Za-zÀÂÄÉÈÊËÎÏÔÖÙÛÜÇàâäéèêëîïôöùûüç]+([ \-'][A-Za-zÀÂÄÉÈÊËÎÏÔÖÙÛÜÇàâäéèêëîïôöùûüç]+)*$/",
        message: 'Le nom n\'est pas valide.',
    )]
    #[ORM\Column(length: 120)]
    private ?string $lastName = null;

    #[Vich\UploadableField(mapping: 'user', fileNameProperty: 'imageAvatar')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255)]
    private ?string $imageAvatar = 'default.jpg';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private ?bool $isDisabled = false;

    #[Assert\NotBlank(message: 'Veuillez entrer votre numéro de téléphone.')]
    #[ORM\Column(length: 15)]
    #[Assert\regex(
        pattern: "/^(((\+|00)32[ ]?(?:\(0\)[ ]?)?)|0){1}(4(60|[789]\d)\/?(\s?\d{2}\.?){2}(\s?\d{2})|(\d\/?\s?\d{3}|\d{2}\/?\s?\d{2})(\.?\s?\d{2}){2})$/",
        message: 'Votre numéro de téléphone n\'est pas valide. Veuillez respecter : +32 ou 04.'
    )]
    private ?string $phoneNumber = null;

    /**
     * @var Collection<int, News>
     */
    #[ORM\OneToMany(targetEntity: News::class, mappedBy: 'author', orphanRemoval: false)]
    private Collection $news;

    /**
     * @var Collection<int, Gallery>
     */
    #[ORM\OneToMany(targetEntity: Gallery::class, mappedBy: 'author')]
    private Collection $galleries;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, TeamUser>
     */
    #[ORM\OneToMany(targetEntity: TeamUser::class, mappedBy: 'user')]
    private Collection $teamUsers;

    #[ORM\Column]
    private ?bool $isAnonymized = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $anonymizedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->news = new ArrayCollection();
        $this->galleries = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->teamUsers = new ArrayCollection();
    }

    /*
     * Méthodes de sérialisation
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'roles' => $this->roles,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phoneNumber' => $this->phoneNumber,
            'imageAvatar' => $this->imageAvatar,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'isDisabled' => $this->isDisabled,
            // Exclure explicitement imageFile
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->roles = $data['roles'];
        $this->firstName = $data['firstName'];
        $this->lastName = $data['lastName'];
        $this->phoneNumber = $data['phoneNumber'];
        $this->imageAvatar = $data['imageAvatar'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->isDisabled = $data['isDisabled'];
        $this->imageFile = null; // Toujours initialiser à null
    }

    public function anonymize(): void
    {
        $idSuffix = $this->getId();

        $this->setEmail('xxxxxx'.$idSuffix.'@anonymized.com');
        $this->setFirstName('xxxxxx'.$idSuffix);
        $this->setLastName('xxxxxx'.$idSuffix);
        $this->setPhoneNumber('xxxxxx'.$idSuffix);
        $this->setImageAvatar('default_avatar.png'); // ou une image d'avatar générique
        $this->setIsAnonymized(true);
        $this->setAnonymizedAt(new \DateTimeImmutable());
        $this->setIsDisabled(true);
        $this->setRoles(['ROLE_ANONYMIZED']);
    }

    /*
     * Getters et Setters
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Nettoyage des données sensibles
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageAvatar(): ?string
    {
        return $this->imageAvatar;
    }

    public function setImageAvatar(?string $imageAvatar): static
    {
        $this->imageAvatar = $imageAvatar;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isDisabled(): ?bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled): static
    {
        $this->isDisabled = $isDisabled;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return Collection<int, News>
     */
    public function getNews(): Collection
    {
        return $this->news;
    }

    public function addNews(News $news): static
    {
        if (!$this->news->contains($news)) {
            $this->news->add($news);
            $news->setAuthor($this);
        }

        return $this;
    }

    public function removeNews(News $news): static
    {
        if ($this->news->removeElement($news)) {
            // set the owning side to null (unless already changed)
            if ($news->getAuthor() === $this) {
                $news->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Gallery>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addGallery(Gallery $gallery): static
    {
        if (!$this->galleries->contains($gallery)) {
            $this->galleries->add($gallery);
            $gallery->setAuthor($this);
        }

        return $this;
    }

    public function removeGallery(Gallery $gallery): static
    {
        if ($this->galleries->removeElement($gallery)) {
            // set the owning side to null (unless already changed)
            if ($gallery->getAuthor() === $this) {
                $gallery->setAuthor(null);
            }
        }

        return $this;
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
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TeamUser>
     */
    public function getTeamUsers(): Collection
    {
        return $this->teamUsers;
    }

    public function addTeamUser(TeamUser $teamUser): static
    {
        if (!$this->teamUsers->contains($teamUser)) {
            $this->teamUsers->add($teamUser);
            $teamUser->setUser($this);
        }

        return $this;
    }

    public function removeTeamUser(TeamUser $teamUser): static
    {
        if ($this->teamUsers->removeElement($teamUser)) {
            // set the owning side to null (unless already changed)
            if ($teamUser->getUser() === $this) {
                $teamUser->setUser(null);
            }
        }

        return $this;
    }

    public function isAnonymized(): ?bool
    {
        return $this->isAnonymized;
    }

    public function setIsAnonymized(bool $isAnonymized): static
    {
        $this->isAnonymized = $isAnonymized;

        return $this;
    }

    public function getAnonymizedAt(): ?\DateTimeImmutable
    {
        return $this->anonymizedAt;
    }

    public function setAnonymizedAt(?\DateTimeImmutable $anonymizedAt): static
    {
        $this->anonymizedAt = $anonymizedAt;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }
}
