<?php

namespace App\Entity\Security;

use App\Entity\UserSettings;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\OneToOne(targetEntity: UserSettings::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserSettings $settings = null;

    /**
     * Propojené externí účty (Google, Apple, …)
     *
     * @var Collection<int, UserIdentity>
     */
    #[ORM\OneToMany(targetEntity: UserIdentity::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $identities;

    /**
     * @var string|null The hashed password; null u účtu založeného jen přes externí přihlášení
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    public function __construct()
    {
        $this->identities = new ArrayCollection();
    }

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', (string) $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getSettings(): ?UserSettings
    {
        return $this->settings;
    }

    public function getNewOrExistSettings(): UserSettings
    {
        if ($this->settings === null) {
            return (new UserSettings())->setUser($this);
        }

        return $this->settings;
    }

    public function setSettings(?UserSettings $settings): static
    {
        $this->settings = $settings;

        if ($settings !== null && $settings->getUser() !== $this) {
            $settings->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, UserIdentity>
     */
    public function getIdentities(): Collection
    {
        return $this->identities;
    }

    public function linkIdentity(string $provider, string $providerUserId, ?string $email): UserIdentity
    {
        $identity = new UserIdentity($this, $provider, $providerUserId, $email);
        $this->identities->add($identity);

        return $identity;
    }
}
