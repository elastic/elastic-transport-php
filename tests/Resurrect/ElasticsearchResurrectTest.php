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

namespace Elastic\Transport\Test\Resurrect;

use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\Resurrect\ElasticsearchResurrect;
use Exception;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

final class ElasticsearchResurrectTest extends TestCase
{
    private ClientInterface $client;
    private ElasticsearchResurrect $resurrect;
    private Node $node;

    public function setUp(): void
    {
        $this->node = $this->createMock(Node::class);

        Psr18ClientDiscovery::prependStrategy(MockClientStrategy::class);
        $this->resurrect = new ElasticsearchResurrect();
        $this->client = $this->resurrect->getClient();
    }

    public function testGetClient()
    {
        $this->assertInstanceOf(ClientInterface::class, $this->resurrect->getClient());
    }

    public function testPingIsTrue()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(200);

        $this->client->addResponse($response);

        $this->assertTrue($this->resurrect->ping($this->node));
    }

    public function testPingIsFalse()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(500);

        $this->client->addResponse($response);

        $this->assertFalse($this->resurrect->ping($this->node));
    }

    public function testPingIsFalseOnConnectionError()
    {
        $exception = new Exception('Connection error');
        $this->client->addException($exception);

        $this->assertFalse($this->resurrect->ping($this->node));
    }
}