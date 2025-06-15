<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[Vich\Uploadable]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[Assert\NotBlank(
        message: 'Le nom ne doit pas être vide.'
    )]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom dois contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne dois pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank(
        message: 'La date ne doit pas être est vide.'
    )]
    #[ORM\Column]
    private ?\DateTimeImmutable $dateTournament = null;

    #[Assert\NotBlank(
        message: 'Le prix ne doit pas être vide.'
    )]
    #[Assert\Range(
        notInRangeMessage: 'Le prix doit être compris entre {{ min }} et {{ max }} euros.',
        min: 4,
        max: 12,
    )]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[Assert\NotBlank(
        message: 'L\'adresse ne doit pas être vide.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[Assert\NotBlank(
        message: 'Le nombres d\'équipes max ne doit pas être vide.'
    )]
    #[ORM\Column]
//    #[Assert\Range(
//        max: 16,
//        maxMessage: 'Le nombre d\équipe max est de {{ limit }}.'
//    )]
    private ?int $teamsMax = null;

    #[ORM\Column]
    private ?bool $isPublished = null;

    #[Assert\NotBlank(
        message: 'Veuillez entrer le nombre de terrain.'
    )]
//    #[Assert\Range(
//        min: 1,
//        max: 8,
//        minMessage: 'Le nombre de terrain doit être de minimum {{ limit }}.',
//        maxMessage: 'Le nombre de terrain doit être de maximum {{ limit }}.'
//    )]
    #[ORM\Column]
    private ?int $groundMax = null;

    #[Assert\NotBlank(
        message: 'Veuillez séléctionner un statut.'
    )]
    #[ORM\Column]
    private array $status = [];

    #[Assert\NotBlank(
        message: 'Veuillez sélectionner un type de tournoi.'
    )]
    #[ORM\Column]
    private array $typeTournament = [];

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'tournament')]
    private Collection $teams;

    /**
     * @var Collection<int, TeamUser>
     */
    #[ORM\OneToMany(targetEntity: TeamUser::class, mappedBy: 'tournament')]
    private Collection $teamUsers;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $games;

    #[ORM\Column]
    private ?\DateTimeImmutable $CreatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $imageTournament = null;

    #[Vich\UploadableField(mapping: 'tournament', fileNameProperty: 'imageTournament')]
    private ?File $imageFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->teamUsers = new ArrayCollection();
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDateTournament(): ?\DateTimeImmutable
    {
        return $this->dateTournament;
    }

    public function setDateTournament(\DateTimeImmutable $dateTournament): static
    {
        $this->dateTournament = $dateTournament;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTeamsMax(): ?int
    {
        return $this->teamsMax;
    }

    public function setTeamsMax(int $teamsMax): static
    {
        $this->teamsMax = $teamsMax;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getGroundMax(): ?int
    {
        return $this->groundMax;
    }

    public function setGroundMax(int $groundMax): static
    {
        $this->groundMax = $groundMax;

        return $this;
    }

    public function getStatus(): array
    {
        return $this->status;
    }

    public function setStatus(array $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTypeTournament(): array
    {
        return $this->typeTournament;
    }

    public function setTypeTournament(array $typeTournament): static
    {
        $this->typeTournament = $typeTournament;

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setTournament($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            // set the owning side to null (unless already changed)
            if ($team->getTournament() === $this) {
                $team->setTournament(null);
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
            $teamUser->setTournament($this);
        }

        return $this;
    }

    public function removeTeamUser(TeamUser $teamUser): static
    {
        if ($this->teamUsers->removeElement($teamUser)) {
            // set the owning side to null (unless already changed)
            if ($teamUser->getTournament() === $this) {
                $teamUser->setTournament(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setTournament($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTournament() === $this) {
                $game->setTournament(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->CreatedAt;
    }

    public function setCreatedAt(\DateTimeImmutable $CreatedAt): static
    {
        $this->CreatedAt = $CreatedAt;

        return $this;
    }

    public function getImageTournament(): ?string
    {
        return $this->imageTournament;
    }

    public function setImageTournament(?string $imageTournament): static
    {
        $this->imageTournament = $imageTournament;

        return $this;
    }

    // vich
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }
    // End Vich

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

}
