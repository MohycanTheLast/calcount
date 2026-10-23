<?php

namespace App\Security;

/**
 * Client ID z env GOOGLE_CLIENT_IDS (web, případně iOS/Android aplikace – oddělené čárkou).
 */
class GoogleIdTokenVerifier extends OidcIdTokenVerifier
{
    protected function jwksUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v3/certs';
    }

    protected function issuers(): array
    {
        return ['accounts.google.com', 'https://accounts.google.com'];
    }
}
