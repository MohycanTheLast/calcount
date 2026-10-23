<?php

namespace App\Security;

use App\Entity\Security\User;
use Psr\Log\LoggerInterface;

/**
 * Ověření e-mailu odkazem (aktivace účtu po registraci).
 *
 * Odkaz je podepsaný (HMAC s APP_SECRET) a platí 48 h, takže nepotřebuje token v DB. Podpis zahrnuje i e-mail –
 * po jeho změně starý odkaz přestane platit. Odkaz vede na frontend (/verify-email), který parametry pošle
 * na POST /api/register/verify.
 *
 * Zatím vypnuté (EMAIL_VERIFICATION_ENABLED=0) – účet z registrace se může rovnou přihlásit.
 * Před zapnutím doplnit odesílání v sendVerificationEmail() (symfony/mailer) a nastavit DEFAULT_URI
 * na veřejnou adresu aplikace.
 */
class EmailVerifier
{
    private const LINK_TTL_SECONDS = 48 * 3600;

    public function __construct(
        private readonly bool $enabled,
        private readonly string $appUrl,
        #[\SensitiveParameter] private readonly string $secret,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function createVerificationUrl(User $user): string
    {
        $expires = time() + self::LINK_TTL_SECONDS;

        return rtrim($this->appUrl, '/').'/verify-email?'.http_build_query([
            'uid' => $user->getId(),
            'expires' => $expires,
            'signature' => $this->sign($user, $expires),
        ]);
    }

    public function sendVerificationEmail(User $user): void
    {
        $url = $this->createVerificationUrl($user);

        // TODO: odeslat e-mail s odkazem $url (symfony/mailer). Do té doby ověření nezapínat –
        // uživatel by se neměl jak aktivovat.
        $this->logger->warning('Verification e-mail not sent – mailer is not wired yet', ['user_id' => $user->getId()]);
    }

    public function isValid(User $user, int $expires, string $signature): bool
    {
        if ($expires < time()) {
            return false;
        }

        return hash_equals($this->sign($user, $expires), $signature);
    }

    private function sign(User $user, int $expires): string
    {
        $payload = sprintf('email-verification|%d|%s|%d', $user->getId(), mb_strtolower((string) $user->getEmail()), $expires);

        return hash_hmac('sha256', $payload, $this->secret);
    }
}
