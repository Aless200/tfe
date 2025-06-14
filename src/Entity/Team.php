<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(
        message: 'le nom de l\'équipe ne peux pas être vide.',
    )]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom de votre équipe doit au moins faire { limit } caractères.',
        maxMessage: 'Le nom de votre équipe ne peux pas dépasser { limit } caractères.',

    )]
    #[ORM\Column(length: 255)]
    private ?string $teamName = null;

    #[ORM\Column]
    private ?int $round = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    /**
     * @var Collection<int, TeamUser>
     */
    #[ORM\OneToMany(targetEntity: TeamUser::class, mappedBy: 'team')]
    private Collection $teamUsers;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'team1', orphanRemoval: true)]
    private Collection $gamesAsTeam1;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'team2', orphanRemoval: true)]
    private Collection $gamesAsTeam2;

    #[ORM\Column(nullable: true)]
    private ?bool $isBye = null;

    public function __construct()
    {
        $this->teamUsers = new ArrayCollection();
        $this->gamesAsTeam1 = new ArrayCollection();
        $this->gamesAsTeam2 = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeamName(): ?string
    {
        return $this->teamName;
    }

    public function setTeamName(string $teamName): static
    {
        $this->teamName = $teamName;
        return $this;
    }

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(int $round): static
    {
        $this->round = $round;
        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;
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
            $teamUser->setTeam($this);
        }

        return $this;
    }

    public function removeTeamUser(TeamUser $teamUser): static
    {
        if ($this->teamUsers->removeElement($teamUser)) {
            // set the owning side to null (unless already changed)
            if ($teamUser->getTeam() === $this) {
                $teamUser->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeam1(): Collection
    {
        return $this->gamesAsTeam1;
    }

    public function addGameAsTeam1(Game $game): static
    {
        if (!$this->gamesAsTeam1->contains($game)) {
            $this->gamesAsTeam1->add($game);
            $game->setTeam1($this);
        }

        return $this;
    }

    public function removeGameAsTeam1(Game $game): static
    {
        if ($this->gamesAsTeam1->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTeam1() === $this) {
                $game->setTeam1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeam2(): Collection
    {
        return $this->gamesAsTeam2;
    }

    public function addGameAsTeam2(Game $game): static
    {
        if (!$this->gamesAsTeam2->contains($game)) {
            $this->gamesAsTeam2->add($game);
            $game->setTeam2($this);
        }

        return $this;
    }

    public function removeGameAsTeam2(Game $game): static
    {
        if ($this->gamesAsTeam2->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTeam2() === $this) {
                $game->setTeam2(null);
            }
        }

        return $this;
    }

    public function isBye(): ?bool
    {
        return $this->isBye;
    }

    public function setIsBye(?bool $isBye): static
    {
        $this->isBye = $isBye;

        return $this;
    }
}

