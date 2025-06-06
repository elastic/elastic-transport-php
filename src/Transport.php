<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the MIT License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport;

use Composer\InstalledVersions;
use Elastic\Transport\Async\OnFailureDefault;
use Elastic\Transport\Async\OnFailureInterface;
use Elastic\Transport\Async\OnSuccessDefault;
use Elastic\Transport\Async\OnSuccessInterface;
use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\Exception\InvalidArgumentException;
use Elastic\Transport\Exception\NoAsyncClientException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Exception;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Promise\Promise;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use function get_class;
use function ini_get;
use function json_encode;
use function php_uname;
use function phpversion;
use function sprintf;
use function strpos;
use function str_replace;
use function strtolower;

final class Transport implements ClientInterface, HttpAsyncClient
{
    const VERSION = "9.0.1";
    const ELASTIC_META_HEADER = "x-elastic-client-meta";

    private ClientInterface $client;
    private LoggerInterface $logger;
    private NodePoolInterface $nodePool;
    /**
     * @var array<mixed>
     */
    private array $headers = [];
    private string $user;
    private string $password;
    private ?RequestInterface $lastRequest = null;
    private ?ResponseInterface $lastResponse = null;
    private string $OSVersion;
    private int $retries = 0;
    private HttpAsyncClient $asyncClient;
    private OnSuccessInterface $onAsyncSuccess;
    private OnFailureInterface $onAsyncFailure;
    private TracerInterface $otelTracer;
    
    public function __construct(
        ClientInterface $client,
        NodePoolInterface $nodePool,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->nodePool = $nodePool;
        $this->logger = $logger;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function getNodePool(): NodePoolInterface
    {
        return $this->nodePool;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getOTelTracer(): TracerInterface
    {
        if (empty($this->otelTracer)) {
            $this->otelTracer = OpenTelemetry::getTracer(
                Globals::tracerProvider()
            );
        }
        return $this->otelTracer;
    }

    public function setOTelTracer(TracerInterface $tracer): self
    {
        $this->otelTracer = $tracer;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setRetries(int $num): self
    {
        if ($num < 0) {
            throw new InvalidArgumentException('The retries number must be a positive integer');
        }
        $this->retries = $num;
        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @return array<mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setUserInfo(string $user, string $password = ''): self
    {
        $this->user = $user;
        $this->password = $password;
        return $this;
    }

    public function setUserAgent(string $name, string $version): self
    {
        $this->headers['User-Agent'] = sprintf(
            "%s/%s (%s %s; PHP %s)",
            $name,
            $version,
            PHP_OS,
            $this->getOSVersion(),
            phpversion()
        );
        return $this;
    }

    /**
     * Set the x-elastic-client-meta header
     * 
     * The header format is specified by the following regex:
     * ^[a-z]{1,}=[a-z0-9\.\-]{1,}(?:,[a-z]{1,}=[a-z0-9\.\-]+)*$
     */
    public function setElasticMetaHeader(string $clientName, string $clientVersion, bool $async = false): self
    {
        $phpSemVersion = sprintf("%d.%d.%d", PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION);
        $meta = sprintf(
            "%s=%s,php=%s,t=%s,a=%d",
            $clientName,
            $this->purgePreReleaseTag($clientVersion),
            $phpSemVersion,
            $this->purgePreReleaseTag(self::VERSION),
            $async ? 1 : 0 // 0=syncronous, 1=asynchronous
        );
        $lib = $this->getClientLibraryInfo();
        if (!empty($lib)) {
            $meta .= sprintf(",%s=%s", $lib[0], $lib[1]);
        }
        $this->headers[self::ELASTIC_META_HEADER] = $meta;
        return $this;
    }

    /**
     * Remove pre-release suffix with a single 'p' letter
     */
    private function purgePreReleaseTag(string $version): string
    {
        return str_replace(['alpha', 'beta', 'snapshot', 'rc', 'pre'], 'p', strtolower($version)); 
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Setup the headers, if not already present 
     */
    private function setupHeaders(RequestInterface $request): RequestInterface
    {
        foreach ($this->headers as $name => $value) {
            if (!$request->hasHeader($name)) {
                $request = $request->withHeader($name, $value);
            }
        }
        return $request;
    }

    /**
     * Setup the user info, if not already present
     */
    private function setupUserInfo(RequestInterface $request): RequestInterface
    {
        $uri = $request->getUri();
        if (empty($uri->getUserInfo())) {
            if (isset($this->user)) {
                $request = $request->withUri($uri->withUserInfo($this->user, $this->password));
            }
        }
        return $request;
    }

    /**
     * Setup the connection Uri 
     */
    private function setupConnectionUri(Node $node, RequestInterface $request): RequestInterface
    {
        $uri = $node->getUri();
        $path = $request->getUri()->getPath();
        
        $nodePath = $uri->getPath();
        // If the node has a path we need to use it as prefix for the existing path
        // @see https://github.com/elastic/elastic-transport-php/pull/20
        if (!empty($nodePath)) {
            $path = sprintf("%s/%s", rtrim($nodePath, '/'), ltrim($path,'/'));
        }
        // If the user information is not in the request, we check if it is present in the node uri
        // @see https://github.com/elastic/elastic-transport-php/issues/18
        if (empty($request->getUri()->getUserInfo()) && !empty($uri->getUserInfo())) {
            $userInfo = explode(':', $uri->getUserInfo());
            $request = $request->withUri(
                $request->getUri()
                    ->withUserInfo($userInfo[0], $userInfo[1] ?? null)
            );
        }
        return $request->withUri(
            $request->getUri()
                ->withHost($uri->getHost())
                ->withPort($uri->getPort())
                ->withScheme($uri->getScheme())
                ->withPath($path)
        );
    }

    private function decorateRequest(RequestInterface $request): RequestInterface
    {
        $request = $this->setupHeaders($request);
        return $this->setupUserInfo($request);
    }

    private function logHeaders(MessageInterface $message): void
    {
        $this->logger->debug(sprintf(
            "Headers: %s\nBody: %s",
            json_encode($message->getHeaders()),
            (string) $message->getBody()
        ));

        $message->getBody()->rewind();
    }

    private function logRequest(string $title, RequestInterface $request): void
    {
        $this->logger->info(sprintf(
            "%s: %s %s", 
            $title,
            $request->getMethod(),
            (string) $request->getUri()
        ), [
            'request' => $request
        ]);
        $this->logHeaders($request);
    }

    private function logResponse(string $title, ResponseInterface $response, int $retry): void
    {
        $this->logger->info(sprintf(
            "%s (retry %d): %d",
            $title,
            $retry, 
            $response->getStatusCode()
        ), [
            'response' => $response,
            'retry' => $retry
        ]);
        $this->logHeaders($response);
    }

    /**
     * @throws NoNodeAvailableException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {   
        if (empty($request->getUri()->getHost())) {
            $node = $this->nodePool->nextNode();
            $request = $this->setupConnectionUri($node, $request);
        }
        $request = $this->decorateRequest($request);
        $this->lastRequest = $request;
        $this->logRequest("Request", $request);

        // OpenTelemetry get tracer
        if (getenv(OpenTelemetry::ENV_VARIABLE_ENABLED)) {
            $tracer = $this->getOTelTracer();
        }

        $lastNetworkException = null;
        $count = -1;
        while ($count < $this->getRetries()) {
            try {
                $count++;
                // OpenTelemetry span start
                if (!empty($tracer)) {
                    if ($request instanceof ServerRequestInterface) {
                        $opts = $request->getAttribute(OpenTelemetry::PSR7_OTEL_ATTRIBUTE_NAME, []);
                    }
                    $spanName = $opts['db.operation.name'] ?? $request->getUri()->getPath();
                    $span = $tracer->spanBuilder($spanName)->startSpan();
                    $span->setAttribute('http.request.method', $request->getMethod());
                    $span->setAttribute('url.full', $this->getFullUrl($request));
                    $span->setAttribute('server.address', $request->getUri()->getHost());
                    $span->setAttribute('server.port', $request->getUri()->getPort());
                    if (!empty($opts)) {
                        $span->setAttributes($opts);
                    }
                }
                $response = $this->client->sendRequest($request);

                $this->lastResponse = $response;
                $this->logResponse("Response", $response, $count);

                return $response;
            } catch (NetworkExceptionInterface $e) {
                $lastNetworkException = $e;
                $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
                if (!empty($span)) {
                    $span->setAttribute('error.type', $e->getMessage());
                }
                if (isset($node)) {
                    $node->markAlive(false);
                    $node = $this->nodePool->nextNode();
                    $request = $this->setupConnectionUri($node, $request);
                }
            } catch (ClientExceptionInterface $e) {
                $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
                if (!empty($span)) {
                    $span->setAttribute('error.type', $e->getMessage());
                }
                throw $e;
            } finally {
                 // OpenTelemetry span end
                if (!empty($span)) {
                    $span->end();
                }
            }
        }
        $exceededMsg = sprintf("Exceeded maximum number of retries (%d)", $this->getRetries());
        $this->logger->error($exceededMsg);
        throw new NoNodeAvailableException($exceededMsg, 0, $lastNetworkException);
    }

    public function setAsyncClient(HttpAsyncClient $asyncClient): self
    {
        $this->asyncClient = $asyncClient;
        return $this;
    }

    /**
     * @throws NoAsyncClientException
     */
    public function getAsyncClient(): HttpAsyncClient
    {
        if (!empty($this->asyncClient)) {
            return $this->asyncClient;
        }
        if ($this->client instanceof HttpAsyncClient) {
            return $this->client;
        }
        try {
            $this->asyncClient = HttpAsyncClientDiscovery::find();
        } catch (Exception $e) {
            throw new NoAsyncClientException(sprintf(
                "I did not find any HTTP library with HttpAsyncClient interface. " .
                "Make sure to install a package providing \"php-http/async-client-implementation\". " .
                "You can also set a specific async library using %s::setAsyncClient()",
                self::class
            ));
        }
        return $this->asyncClient;
    }

    public function setAsyncOnSuccess(OnSuccessInterface $success): self
    {
        $this->onAsyncSuccess = $success;
        return $this;
    }

    public function getAsyncOnSuccess(): OnSuccessInterface
    {
        if (empty($this->onAsyncSuccess)) {
            $this->onAsyncSuccess = new OnSuccessDefault();
        }
        return $this->onAsyncSuccess;
    }

    public function setAsyncOnFailure(OnFailureInterface $failure): self
    {
        $this->onAsyncFailure = $failure;
        return $this;
    }

    public function getAsyncOnFailure(): OnFailureInterface
    {
        if (empty($this->onAsyncFailure)) {
            $this->onAsyncFailure = new OnFailureDefault();
        }
        return $this->onAsyncFailure;
    }

    /**
     * @throws Exception
     */
    public function sendAsyncRequest(RequestInterface $request): Promise
    {
        $client = $this->getAsyncClient();
        $node = null;
        if (empty($request->getUri()->getHost())) {
            $node = $this->nodePool->nextNode();
            $request = $this->setupConnectionUri($node, $request);
        }
        $request = $this->decorateRequest($request);
        $this->lastRequest = $request;
        $this->logRequest("Async Request", $request);

        $count = 0;
        $promise = $client->sendAsyncRequest($request);

        // onFulfilled callable
        $onFulfilled = function (ResponseInterface $response) use (&$count) {
            $this->lastResponse = $response;
            $this->logResponse("Async Response", $response, $count);
            return $this->getAsyncOnSuccess()->success($response, $count);
        };

        // onRejected callable
        $onRejected = function (Exception $e) use ($client, $request, &$count, $node) {
            $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
            $this->getAsyncOnFailure()->failure($e, $request, $count, $node ?? null);
            if (isset($node)) {
                $node->markAlive(false);
                $node = $this->nodePool->nextNode();
                $request = $this->setupConnectionUri($node, $request);
            }
            $count++;
            return $client->sendAsyncRequest($request);
        };
        
        // Add getRetries() callables using then()
        for ($i=0; $i < $this->getRetries(); $i++) {
            $promise = $promise->then($onFulfilled, $onRejected);
        }
        // Add the last getRetries()+1 callable for managing the exceeded error
        $promise = $promise->then($onFulfilled, function(Exception $e) use (&$count) {
            $exceededMsg = sprintf("Exceeded maximum number of retries (%d)", $this->getRetries());
            $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
            $this->logger->error($exceededMsg);
            throw new NoNodeAvailableException(sprintf("%s: %s", $exceededMsg, $e->getMessage()));
        });
        return $promise;
    }

    /**
     * Get the OS version using php_uname if available
     * otherwise it returns an empty string
     */
    private function getOSVersion(): string
    {
        if (!isset($this->OSVersion)) {
            $disable_functions = (string) ini_get('disable_functions');
            $this->OSVersion = strpos(strtolower($disable_functions), 'php_uname') !== false
                ? ''
                : php_uname("r");
        }
        return $this->OSVersion;
    }

    /**
     * Returns the name and the version of the Client HTTP library used
     * Here a list of supported libraries:
     * gu => guzzlehttp/guzzle
     * sy => symfony/http-client
     * ec => elastic curl client (Elastic\Transport\Client\Curl)
     * 
     * @return array<mixed>
     */
    private function getClientLibraryInfo(): array
    {
        $clientClass = get_class($this->client);
        if (false !== strpos($clientClass, 'GuzzleHttp\Client')) {
            return ['gu', InstalledVersions::getPrettyVersion('guzzlehttp/guzzle')]; 
        }
        if (false !== strpos($clientClass, 'Symfony\Component\HttpClient')) {
            return ['sy', InstalledVersions::getPrettyVersion('symfony/http-client')];
        }
        if (false !== strpos($clientClass, 'Elastic\Transport\Client\Curl')) {
            return ['ec', Transport::VERSION];
        }
        return [];
    }

    /**
     * Return the full URL in the format
     * scheme://host:port/path?query_string
     */
    private function getFullUrl(RequestInterface $request): string
    {
        $fullUrl = sprintf(
            "%s://%s:%s%s", 
            $request->getUri()->getScheme(),
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $request->getUri()->getPath()
        );
        $queryString = $request->getUri()->getQuery();
        if (!empty($queryString)) {
            $fullUrl .= '?' . $queryString;
        }
        return $fullUrl;
    }
}
