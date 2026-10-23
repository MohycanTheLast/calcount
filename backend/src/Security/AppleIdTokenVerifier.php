<?php

namespace App\Security;

/**
 * Client ID z env APPLE_CLIENT_IDS (Services ID pro web, bundle ID iOS aplikace – oddělené čárkou).
 */
class AppleIdTokenVerifier extends OidcIdTokenVerifier
{
    protected function jwksUrl(): string
    {
        return 'https://appleid.apple.com/auth/keys';
    }

    protected function issuers(): array
    {
        return ['https://appleid.apple.com'];
    }
}
