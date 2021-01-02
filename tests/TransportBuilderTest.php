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

namespace Elastic\Transport\Test;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\TransportBuilder;
use Psr\Http\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\Test\TestLogger;

final class TransportBuilderTest extends TestCase
{
    private $client;
    private $connectionPool;
    private $logger;

    public function setUp(): void
    {
        $this->client = $this->createStub(ClientInterface::class);
        $this->connectionPool = $this->createStub(SimpleConnectionPool::class);
        $this->logger = new TestLogger();
    }

    public function testCreate()
    {
        $builder = TransportBuilder::create();

        $this->assertInstanceOf(TransportBuilder::class, $builder);
    }

    public function testSetClient()
    {
        $builder = TransportBuilder::create();
        $result = $builder->setClient($this->client);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($builder, $result);
    }

    public function testSetConnectionPool()
    {
        $builder = TransportBuilder::create();
        $result = $builder->setConnectionPool($this->connectionPool);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($builder, $result);
    }

    public function testSetLogger()
    {
        $builder = TransportBuilder::create();
        $result = $builder->setLogger($this->logger);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($builder, $result);
    }
}