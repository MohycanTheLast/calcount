<?php

namespace App\Entity\Security;

use App\Repository\PersonalAccessTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Osobní API token – dlouhodobý klíč pro automatizace (iOS Zkratky), které nemají jak obnovovat JWT.
 *
 * V DB je jen SHA-256 hash, plný token uživatel uvidí jen jednou při vytvoření. Token má omezená oprávnění
 * (scopes) – smí volat jen endpointy svého scope (viz PersonalAccessTokenAuthenticator a security.yaml).
 * Nevyprší, dá se ale odvolat.
 */
#[ORM\Entity(repositoryClass: PersonalAccessTokenRepository::class)]
#[ORM\Table(name: 'personal_access_token')]
#[ORM\UniqueConstraint(name: 'UNIQ_PAT_TOKEN_HASH', columns: ['token_hash'])]
#[ORM\Index(name: 'IDX_PAT_USER', columns: ['user_id'])]
class PersonalAccessToken
{
    // Předpona odliší osobní token od JWT už podle hlavičky Authorization
    public const PREFIX = 'cc_pat_';

    public const SCOPE_ACTIVITY_WRITE = 'activity:write';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 64)]
    private string $tokenHash;

    // Začátek tokenu pro rozpoznání v seznamu (cc_pat_ab12cd…)
    #[ORM\Column(length: 20)]
    private string $displayPrefix;

    /** @var list<string> */
    #[ORM\Column]
    private array $scopes;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /**
     * @param list<string> $scopes
     */
    private function __construct(User $user, string $name, string $plainToken, array $scopes)
    {
        $this->user = $user;
        $this->name = $name;
        $this->tokenHash = self::hash($plainToken);
        $this->displayPrefix = substr($plainToken, 0, strlen(self::PREFIX) + 6);
        $this->scopes = $scopes;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Vytvoří token s náhodnou tajnou částí.
     *
     * @param list<string> $scopes
     *
     * @return array{0: self, 1: string} [entita, plný token – ukázat uživateli jen teď]
     */
    public static function generate(User $user, string $name, array $scopes): array
    {
        $plainToken = self::PREFIX.bin2hex(random_bytes(20));

        return [new self($user, $name, $plainToken, $scopes), $plainToken];
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayPrefix(): string
    {
        return $this->displayPrefix;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function markUsed(): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function revoke(): self
    {
        $this->revokedAt ??= new \DateTimeImmutable();

        return $this;
    }
}
