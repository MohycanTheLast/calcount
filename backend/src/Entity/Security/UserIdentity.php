<?php

namespace App\Entity\Security;

use App\Repository\UserIdentityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Externí účet propojený s uživatelem (přihlášení přes Google, Apple; později např. Strava).
 * Uživatel jich může mít víc, identifikuje je dvojice provider + providerUserId (claim „sub“).
 */
#[ORM\Entity(repositoryClass: UserIdentityRepository::class)]
#[ORM\Table(name: 'user_identity')]
#[ORM\UniqueConstraint(name: 'UNIQ_PROVIDER_USER', fields: ['provider', 'providerUserId'])]
class UserIdentity
{
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_APPLE = 'apple';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'identities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $provider;

    #[ORM\Column(length: 255)]
    private string $providerUserId;

    // E-mail v okamžiku propojení (u Apple může být skrytá relay adresa)
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email;

    #[ORM\Column]
    private \DateTimeImmutable $linkedAt;

    public function __construct(User $user, string $provider, string $providerUserId, ?string $email)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->email = $email;
        $this->linkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLinkedAt(): \DateTimeImmutable
    {
        return $this->linkedAt;
    }
}
