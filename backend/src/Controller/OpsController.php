<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provozní endpointy volané ručně (viz backend/http/ops.http).
 *
 * Chráněné tokenem v hlavičce X-Ops-Token (env OPS_TOKEN, min. 32 znaků).
 * Bez nastaveného tokenu jsou endpointy vypnuté. Leží mimo /api, takže na ně nedopadá JWT firewall.
 */
final class OpsController extends AbstractController
{
    private const MIN_TOKEN_LENGTH = 32;

    public function __construct(
        #[Autowire(env: 'default::OPS_TOKEN')]
        private readonly ?string $opsToken,
        #[Autowire('%kernel.project_dir%/var/ops/cache-clear-last.json')]
        private readonly string $protocolFile,
        private readonly KernelInterface $kernel,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/ops/cache-clear', methods: ['POST'])]
    public function clearCache(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return $this->notFound();
        }

        // Cache se maže až po odeslání odpovědi. Smazání uprostřed requestu by mohlo shodit
        // zbytek jeho zpracování – kontejner si služby líně načítá právě z mazaných souborů.
        $this->dispatcher->addListener(KernelEvents::TERMINATE, fn () => $this->runCacheClear(), -2048);

        return $this->json([
            'status' => 'scheduled',
            'environment' => $this->kernel->getEnvironment(),
            'requestedAt' => date(DATE_ATOM),
            'protocol' => 'GET /ops/cache-clear',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/ops/cache-clear', methods: ['GET'])]
    public function lastProtocol(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return $this->notFound();
        }

        if (!is_file($this->protocolFile)) {
            return $this->json(['status' => 'never_run']);
        }

        return new JsonResponse(file_get_contents($this->protocolFile), json: true);
    }

    private function runCacheClear(): void
    {
        $startedAt = microtime(true);
        $output = new BufferedOutput();

        try {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);

            $exitCode = $application->run(
                new ArrayInput(['command' => 'cache:clear', '--no-interaction' => true]),
                $output
            );
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output->writeln($e->getMessage());
        }

        // V produkci opcache nekontroluje změny souborů – bez resetu by běžel starý kód
        $opcacheReset = function_exists('opcache_reset') && opcache_reset();

        $protocol = [
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'environment' => $this->kernel->getEnvironment(),
            'exitCode' => $exitCode,
            'opcacheReset' => $opcacheReset,
            'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
            'finishedAt' => date(DATE_ATOM),
            'output' => array_values(array_filter(array_map('trim', explode("\n", $output->fetch())))),
        ];

        (new Filesystem())->dumpFile(
            $this->protocolFile,
            json_encode($protocol, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->logger->info('Ops: cache clear finished', ['status' => $protocol['status'], 'exitCode' => $exitCode]);
    }

    private function isAuthorized(Request $request): bool
    {
        $expected = (string) $this->opsToken;
        if (strlen($expected) < self::MIN_TOKEN_LENGTH) {
            $this->logger->warning('Ops: OPS_TOKEN is not set or shorter than {min} chars, endpoint disabled', ['min' => self::MIN_TOKEN_LENGTH]);
            return false;
        }

        if (!hash_equals($expected, (string) $request->headers->get('X-Ops-Token', ''))) {
            $this->logger->warning('Ops: invalid token', ['ip' => $request->getClientIp(), 'path' => $request->getPathInfo()]);
            return false;
        }

        return true;
    }

    private function notFound(): JsonResponse
    {
        // 404 místo 401/403 – endpoint se bez platného tokenu navenek neprozrazuje
        return $this->json(['error' => 'Not Found'], Response::HTTP_NOT_FOUND);
    }
}
