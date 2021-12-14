<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport;

use Composer\InstalledVersions;
use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\ConnectionPoolInterface;
use Elastic\Transport\Exception\InvalidArgumentException;
use Exception;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function json_encode;
use function sprintf;

final class Transport implements ClientInterface
{
    const VERSION = "8.x";

    private ClientInterface $client;
    private LoggerInterface $logger;
    private ConnectionPoolInterface $connectionPool;
    private array $headers = [];
    private string $user;
    private string $password;
    private RequestInterface $lastRequest;
    private ResponseInterface $lastResponse;
    private string $OSVersion;
    private int $retries = 0;
    private bool $isAsync = false; // syncronous HTTP call as default

    public function __construct(
        ClientInterface $client,
        HttpAsyncClient $asyncClient,
        ConnectionPoolInterface $connectionPool,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->asyncClient = $asyncClient;
        $this->connectionPool = $connectionPool;
        $this->logger = $logger;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function getConnectionPool(): ConnectionPoolInterface
    {
        return $this->connectionPool;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

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
    public function setElasticMetaHeader(string $clientName, string $clientVersion): self
    {
        $phpSemVersion = sprintf("%d.%d.%d", PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION);
        $meta = sprintf(
            "%s=%s,php=%s,t=%s,a=%d",
            $clientName,
            $this->purgePreReleaseTag($clientVersion),
            $phpSemVersion,
            $this->purgePreReleaseTag(self::VERSION),
            0 // syncronous
        );
        $lib = $this->getClientLibraryInfo();
        if (!empty($lib)) {
            $meta .= sprintf(",%s=%s", $lib[0], $lib[1]);
        }
        $this->headers['x-elastic-client-meta'] = $meta;
        return $this;
    }

    /**
     * Remove pre-release suffix with a single 'p' letter
     */
    private function purgePreReleaseTag(string $version): string
    {
        return str_replace(['alpha', 'beta', 'snapshot', 'rc', 'pre'], 'p', strtolower($version)); 
    }

    public function getLastRequest(): RequestInterface
    {
        return $this->lastRequest;
    }

    public function getLastResponse(): ResponseInterface
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
    private function setupConnectionUri(Connection $connection, RequestInterface $request): RequestInterface
    {
        $host = $connection->getUri()->getHost();
        $port = $connection->getUri()->getPort();
        $scheme = $connection->getUri()->getScheme();

        return $request->withUri(
            $request->getUri()
                ->withHost($host)
                ->withPort($port)
                ->withScheme($scheme)
        );
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {   
        // Set the host if empty
        if (empty($request->getUri()->getHost())) {
            $connection = $this->connectionPool->nextConnection();
            $request = $this->setupConnectionUri($connection, $request);
        }

        $request = $this->setupHeaders($request);
        $request = $this->setupUserInfo($request);
        
        $this->lastRequest = $request;
        $this->logger->info(sprintf(
            "Send Request: %s %s", 
            $request->getMethod(),
            (string) $request->getUri()
        ));
        $this->logger->debug(sprintf(
            "Headers: %s\nBody: %s",
            json_encode($request->getHeaders()),
            $request->getBody()->getContents()
        ));

        $count = -1;
        while ($count < $this->getRetries()) {
            try {
                $count++;
                $response = $this->client->sendRequest($request);
                $this->lastResponse = $response;
                
                $this->logger->info(sprintf(
                    "Response (retry %d): %d",
                    $count, 
                    $response->getStatusCode()
                ));
                $this->logger->debug(sprintf(
                    "Headers: %s\nBody: %s",
                    json_encode($response->getHeaders()),
                    $response->getBody()->getContents()
                ));

                return $response;
            } catch (NetworkExceptionInterface $e) {
                $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
                if ($count >= $this->getRetries()) {
                    $connection->markAlive(false);
                    throw $e;
                }
            } catch (ClientExceptionInterface $e) {
                $this->logger->error(sprintf("Retry %d: %s", $count, $e->getMessage()));
                throw $e;
            }
        } 
    }

    public function sendAsyncRequest(RequestInterface $request): Promise
    {
        // Set the host if empty
        if (empty($request->getUri()->getHost())) {
            $connection = $this->connectionPool->nextConnection();
            $request = $this->setupConnectionUri($connection, $request);
        }

        $request = $this->setupHeaders($request);
        $request = $this->setupUserInfo($request);
        
        $this->lastRequest = $request;
        $this->logger->info(sprintf(
            "Send Async Request: %s %s", 
            $request->getMethod(),
            (string) $request->getUri()
        ));
        $this->logger->debug(sprintf(
            "Headers: %s\nBody: %s",
            json_encode($request->getHeaders()),
            $request->getBody()->getContents()
        ));

        $promise = $this->asyncClient->sendAsyncRequest($request);
        $promise->then(
            // onFulfilled
            function (ResponseInterface $response) {
                $this->lastResponse = $response;

                $this->logger->info(sprintf(
                    "Async Response: %d", 
                    $response->getStatusCode()
                ));
                $this->logger->debug(sprintf(
                    "Headers: %s\nBody: %s",
                    json_encode($response->getHeaders()),
                    $response->getBody()->getContents()
                ));
            },
            // onRejected
            function (Exception $e) use ($connection) {
                $connection->markAlive(false);
                $this->logger->error(sprintf("Async error: %s", $e->getMessage()));
            }
        );
        return $promise;
    }

    /**
     * Get the OS version using php_uname if available
     * otherwise it returns an empty string
     */
    private function getOSVersion(): string
    {
        if ($this->OSVersion === null) {
            $this->OSVersion = strpos(strtolower(ini_get('disable_functions')), 'php_uname') !== false
                ? ''
                : php_uname("r");
        }
        return $this->OSVersion;
    }

    /**
     * Returns the name and the version of the Client HTTP library used
     * Here a list of supported libraries:
     * gu => guzzlehttp/guzzle
     */
    private function getClientLibraryInfo(): array
    {
        switch(get_class($this->client)) {
            case 'GuzzleHttp\Client':
                $version = InstalledVersions::getPrettyVersion('guzzlehttp/guzzle');
                return ['gu', $version];
            default:
                return [];
        }
    }
}