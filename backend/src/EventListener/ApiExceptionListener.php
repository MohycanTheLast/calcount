<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Jednotný formát chyb API (/api/*):
 *
 *   { "error": "Zpráva pro uživatele" }
 *   { "error": "…", "violations": [{ "field": "kcal", "message": "…" }] }   – 422, neplatné tělo požadavku
 *
 * `error` je vždy text, který může frontend rovnou zobrazit (u validace první porušené pravidlo).
 * Stejný tvar vracejí i kontrolery, které chybu sestavují samy (`$this->json(['error' => …], 400)`).
 */
final class ApiExceptionListener
{
    /**
     * Výjimky z /api převede na JSON. Priorita -64: až po zalogování (ErrorListener, 0), ale před výchozím
     * HTML/problem+json výstupem Symfony (-128). Neočekávané chyby (500) zůstávají na Symfony.
     */
    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $validation = $exception->getPrevious();
        if ($validation instanceof ValidationFailedException) {
            $violations = array_map(
                static fn (ConstraintViolationInterface $v) => ['field' => $v->getPropertyPath(), 'message' => (string) $v->getMessage()],
                iterator_to_array($validation->getViolations())
            );
            $body = ['error' => $violations[0]['message'] ?? 'Neplatný požadavek.', 'violations' => $violations];
        } else {
            // Text výjimky se neposílá – může obsahovat interní detaily (např. název entity u 404)
            $body = ['error' => Response::$statusTexts[$exception->getStatusCode()] ?? 'Error'];
        }

        $event->setResponse(new JsonResponse($body, $exception->getStatusCode(), $exception->getHeaders()));
    }

    /**
     * 401 z JWT autentizace (chybějící, neplatný nebo prošlý token, odmítnutý refresh token) – místo
     * { code, message } z LexikJWTAuthenticationBundle stejný tvar jako zbytek API.
     */
    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    #[AsEventListener(event: Events::JWT_INVALID)]
    #[AsEventListener(event: Events::JWT_EXPIRED)]
    #[AsEventListener(event: Events::AUTHENTICATION_FAILURE)]
    public function onJwtFailure(AuthenticationFailureEvent $event): void
    {
        $response = $event->getResponse();
        $data = $response !== null ? json_decode((string) $response->getContent(), true) : null;

        $wwwAuthenticate = $response?->headers->get('WWW-Authenticate');

        $event->setResponse(new JsonResponse(
            ['error' => is_array($data) && is_string($data['message'] ?? null) ? $data['message'] : 'Unauthorized'],
            $response?->getStatusCode() ?? Response::HTTP_UNAUTHORIZED,
            $wwwAuthenticate !== null ? ['WWW-Authenticate' => $wwwAuthenticate] : []
        ));
    }
}
