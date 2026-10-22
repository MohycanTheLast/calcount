<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class JwtRefreshResponseListener
{
    public function __construct(
        private readonly int $accessTokenTtlSeconds
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getPathInfo() !== '/api/token/refresh') {
            return;
        }

        if (!$response instanceof JsonResponse) {
            return;
        }

        $data = json_decode($response->getContent() ?: '', true);
        if (!is_array($data)) {
            return;
        }

        if (isset($data['token']) && !isset($data['access_token'])) {
            $data['access_token'] = $data['token'];
            unset($data['token']);
        }

        if (!isset($data['expires_in']) && isset($data['access_token'])) {
            $data['expires_in'] = $this->accessTokenTtlSeconds;
        }

        $response->setData($data);
    }
}
