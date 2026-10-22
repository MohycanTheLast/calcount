<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DebugController extends AbstractController
{
    #[Route('/api/debug/auth-header', methods: ['GET'])]
    public function authHeader(Request $request): JsonResponse
    {
        $auth = $request->headers->get('Authorization');

        return $this->json([
            'has_authorization_header' => is_string($auth) && $auth !== '',
            'starts_with_bearer' => is_string($auth) && str_starts_with($auth, 'Bearer '),
        ]);
    }
}
