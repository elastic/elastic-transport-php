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

use Elastic\Transport\Client\Curl;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\NodePool\SimpleNodePool;
use Elastic\Transport\Exception;
use Elastic\Transport\NodePool\Resurrect\NoResurrect;
use Elastic\Transport\NodePool\Selector\RoundRobin;
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use OpenTelemetry\API\Trace\TracerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class TransportBuilder
{
    protected ClientInterface $client;
    protected NodePoolInterface $nodePool;
    protected LoggerInterface $logger;
    /**
     * @var array<string>
     */
    protected array $hosts = [];
    protected TracerInterface $OTelTracer;

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

    public function getClient(): ClientInterface
    {
        if (empty($this->client)) {
            try {
                $this->client = Psr18ClientDiscovery::find();
            } catch (NotFoundException $e) {
                $this->client = new Curl();
            }
        }
        return $this->client;
    }

    public function setNodePool(NodePoolInterface $nodePool): self
    {
        $this->nodePool = $nodePool;
        return $this;
    }

    public function getNodePool(): NodePoolInterface
    {
        if (empty($this->nodePool)) {
            $this->nodePool = new SimpleNodePool(
                new RoundRobin(),
                new NoResurrect()
            );
        }
        return $this->nodePool;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        if (empty($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    /**
     * @param array<string> $hosts
     */
    public function setHosts(array $hosts): self
    {
        $this->hosts = $hosts;
        return $this;
    }

    /**
     * @return array<string>
     */
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
        return new Transport(
            $this->getClient(),
            $this->getNodePool()->setHosts($this->hosts),
            $this->getLogger()
        );
    }

    /**
     * Return the URL of Elastic Cloud from the Cloud ID
     * 
     * @throws Exception\CloudIdParseException
     */
    private function parseElasticCloudId(string $cloudId): string
    {
        if (strpos($cloudId, ':') !== false) {
            list($name, $encoded) = explode(':', $cloudId, 2);
            $base64 = base64_decode($encoded, true);
            if ($base64 !== false && strpos($base64, '$') !== false) {
                list($uri, $uuids) = explode('$', $base64);
                return sprintf("https://%s.%s", $uuids, $uri);
            }
        }
        throw new Exception\CloudIdParseException(sprintf(
            'Cloud ID %s is not valid', 
            $name ?? ''
        ));
    }
}