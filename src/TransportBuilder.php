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

use Elastic\Transport\ConnectionPool\ConnectionPoolInterface;
use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\Exception;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class TransportBuilder
{
    protected $client;
    protected $connectionPool;
    protected $logger;
    protected $hosts = [];

    final public function __construct()
    {
    }

    public static function create(): TransportBuilder
    {
        return new static();
    }

    public function setClient(ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function setConnectionPool(ConnectionPoolInterface $connectionPool): self
    {
        $this->connectionPool = $connectionPool;
        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function setHosts(array $hosts): self
    {
        $this->hosts = $hosts;
        return $this;
    }

    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function setCloudId(string $cloudId): self
    {
        $this->hosts = [$this->parseElasticCloudId($cloudId)];
        return $this;
    }

    public function build(): Transport
    {
        $connectionPool = $this->connectionPool ?? new SimpleConnectionPool;
        $connectionPool->setHosts($this->hosts);

        $transport = new Transport(
            $this->client ?? new GuzzleClient,
            $connectionPool,
            $this->logger ?? new NullLogger
        );
        return $transport;
    }

    /**
     * Return the URL of Elastic Cloud from the Cloud ID
     */
    private function parseElasticCloudId(string $cloudId): string
    {
        try {
            list($name, $encoded) = explode(':', $cloudId);
            list($uri, $uuids)    = explode('$', base64_decode($encoded));
            list($es,)            = explode(':', $uuids);

            return sprintf("https://%s.%s", $es, $uri);
        } catch (Throwable $t) {
            throw new Exception\CloudIdParseException(
                'Could ID not valid'
            );
        }
    }
}