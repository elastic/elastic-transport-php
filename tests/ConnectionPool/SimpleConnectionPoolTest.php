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

namespace Elastic\Transport\Test\ConnectionPool;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\Resurrect\ResurrectInterface;
use Elastic\Transport\ConnectionPool\Selector\SelectorInterface;
use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\Exception\NoAliveException;
use PHPUnit\Framework\TestCase;

final class SimpleConnectionPoolTest extends TestCase
{
    public function setUp(): void
    {
        $this->selector = $this->createStub(SelectorInterface::class);
        $this->resurrect = $this->createStub(ResurrectInterface::class);
    }

    public function testConstructorWithoutParameters()
    {
        $connectionPool = new SimpleConnectionPool();
        $this->assertInstanceOf(SimpleConnectionPool::class, $connectionPool);
    }
   
    public function testConstructorWithSelectorParameter()
    {
        $connectionPool = new SimpleConnectionPool($this->selector);
        $this->assertInstanceOf(SimpleConnectionPool::class, $connectionPool);
    }

    public function testConstructorWithResurrectParameter()
    {
        $connectionPool = new SimpleConnectionPool(null, $this->resurrect);
        $this->assertInstanceOf(SimpleConnectionPool::class, $connectionPool);
    }

    public function testConstructorWithSelectorAndResurrectParameters()
    {
        $connectionPool = new SimpleConnectionPool($this->selector, $this->resurrect);
        $this->assertInstanceOf(SimpleConnectionPool::class, $connectionPool);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetHosts()
    {
        $hosts = [
            '192.168.1.10',
            '192.168.1.11',
        ];
        $connectionPool = new SimpleConnectionPool();
        $connectionPool->setHosts($hosts);
    }

    public function testNextConnectionWithDefaultValues()
    {
        $hosts = [
            '192.168.1.10',
            '192.168.1.11',
        ];
        $connectionPool = new SimpleConnectionPool();
        $connectionPool->setHosts($hosts);

        $connection = $connectionPool->nextConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $host1 = $connection->getUri()->getHost();
        $this->assertContains($host1, $hosts);

        $connection = $connectionPool->nextConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $host2 = $connection->getUri()->getHost();
        $this->assertContains($host1, $hosts);
        $this->assertNotEquals($host1, $host2);

        $connection = $connectionPool->nextConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals($host1, $connection->getUri()->getHost());

        $connection = $connectionPool->nextConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals($host2, $connection->getUri()->getHost());
    }

    public function testNextConnectionWithMockSelectorWithDeadNodes()
    {
        $hosts = [
            '192.168.1.10',
            '192.168.1.11',
        ];

        $connection = $this->createStub(Connection::class);
        $connection->method('isAlive')
            ->will($this->onConsecutiveCalls(false, false));

        $this->selector->method('nextConnection')
            ->willReturn($connection);

        $connectionPool = new SimpleConnectionPool($this->selector);
        $connectionPool->setHosts($hosts);

        $this->expectException(NoAliveException::class);
        $this->expectExceptionMessage(sprintf(
            "No alive connection. All the %d nodes seem to be down.",
            count($hosts)
        ));
        $connection = $connectionPool->nextConnection();
    }
}