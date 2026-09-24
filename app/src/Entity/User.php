<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[UniqueEntity('email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email = '';

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private bool $blocked = false;

    #[ORM\Column(length: 8)]
    private string $locale = 'en';

    #[ORM\Column(length: 16)]
    private string $theme = 'light';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SocialAccount::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $socialAccounts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CandidateAttributeValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attributeValues;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Project::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Cv::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $cvs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->socialAccounts = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->cvs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): void
    {
        $this->roles = array_values(array_unique($roles));
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): void
    {
        $this->blocked = $blocked;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getSocialAccounts(): Collection
    {
        return $this->socialAccounts;
    }

    public function addSocialAccount(SocialAccount $account): void
    {
        if (!$this->socialAccounts->contains($account)) {
            $this->socialAccounts->add($account);
        }
    }

    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    public function addAttributeValue(CandidateAttributeValue $value): void
    {
        if (!$this->attributeValues->contains($value)) {
            $this->attributeValues->add($value);
        }
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    public function eraseCredentials(): void
    {
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'roles' => $this->roles,
            'blocked' => $this->blocked,
            'locale' => $this->locale,
            'theme' => $this->theme,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'version' => $this->version,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->roles = $data['roles'];
        $this->blocked = $data['blocked'];
        $this->locale = $data['locale'];
        $this->theme = $data['theme'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->version = $data['version'];
        $this->socialAccounts = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->cvs = new ArrayCollection();
    }
}
