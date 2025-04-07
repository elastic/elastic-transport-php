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

namespace Elastic\Transport\Test;

use Elastic\Transport\Exception\CloudIdParseException;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\Transport;
use Elastic\Transport\TransportBuilder;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TransportBuilderTest extends TestCase
{
    private Stub|ClientInterface $client;
    private Stub|NodePoolInterface $nodePool;
    private Stub|LoggerInterface $logger;
    private TransportBuilder $builder;

    public function setUp(): void
    {
        $this->client = $this->createStub(ClientInterface::class);
        $this->nodePool = $this->createStub(NodePoolInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        
        Psr18ClientDiscovery::prependStrategy(MockClientStrategy::class);
        $this->builder = TransportBuilder::create();
    }

    public function testCreate()
    {
        $this->assertInstanceOf(TransportBuilder::class, $this->builder);
    }

    public function testSetClient()
    {
        $result = $this->builder->setClient($this->client);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->client, $this->builder->getClient());
    }

    public function testGetClient()
    {
        $client = $this->builder->getClient();
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testSetNodePool()
    {
        $result = $this->builder->setNodePool($this->nodePool);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->nodePool, $this->builder->getNodePool());
    }

    public function testGetNodePool()
    {
        $this->assertInstanceOf(NodePoolInterface::class, $this->builder->getNodePool());
    }

    public function testSetLogger()
    {
        $result = $this->builder->setLogger($this->logger);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->logger, $this->builder->getLogger());
    }

    public function testGetLogger()
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->builder->getLogger());
    }

    public function testSetHosts()
    {
        $hosts = ['xxx', 'yyy'];
        $result = $this->builder->setHosts($hosts);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($hosts, $this->builder->getHosts());
    }

    public function testGetHosts()
    {
        $this->assertIsArray($this->builder->getHosts());
    }

    public function testSetEmptyHosts()
    {
        $hosts = [];
        $result = $this->builder->setHosts($hosts);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($hosts, $this->builder->getHosts());
    }

    public function testSetInvalidCloudId()
    {
        $this->expectException(CloudIdParseException::class);
        $this->builder->setCloudId('xxx');
    }

    public function testSetValidCloudId()
    {
        $result = $this->builder->setCloudId(sprintf(
            "xxx:%s", 
            base64_encode('aaa$cloud.elastic.co:1234567890')
        ));

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertContains('https://cloud.elastic.co.aaa', $this->builder->getHosts());
    }

    public function testBuildWithDefault()
    {
        $this->assertInstanceOf(Transport::class, $this->builder->build());
    }

    public function testBuildWithCustoms()
    {
        $this->builder->setClient($this->client);
        
        $this->builder->setNodePool($this->nodePool);
        $this->nodePool->method('setHosts')
            ->willReturn($this->nodePool);

        $this->builder->setLogger($this->logger);

        $transport = $this->builder->build();
        $this->assertInstanceOf(Transport::class, $transport);
        $this->assertEquals($this->client, $transport->getClient());
        $this->assertEquals($this->nodePool, $transport->getNodePool());
        $this->assertEquals($this->logger, $transport->getLogger());
    }
}