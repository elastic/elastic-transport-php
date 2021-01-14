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

namespace Elastic\Transport\Test\Selector;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\Selector\RoundRobin;
use Elastic\Transport\Exception\NoConnectionAvailableException;
use PHPUnit\Framework\TestCase;

final class RoundRobinTest extends TestCase
{
    public function setUp(): void
    {
        $this->selector = new RoundRobin();
        $this->connection = $this->createStub(Connection::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetConnections()
    {
        $connections = [ $this->connection ];
        $this->selector->setConnections($connections);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetEmptyConnections()
    {
        $connections = [];
        $this->selector->setConnections($connections);
    }

    public function testNextConnectionWithEmptyConnectionsThrowException()
    {
        $this->expectException(NoConnectionAvailableException::class);
        $connection = $this->selector->nextConnection();
    }

    public function testNextConnectionWithOneConnection()
    {
        $connections = [ $this->connection ];
        $this->selector->setConnections($connections);
        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[0], $connection);

        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[0], $connection);
    }

    public function testNextConnectionWithTwoConnections()
    {
        $connection2 = $this->createStub(Connection::class);
        $connections = [ $this->connection, $connection2 ];
        $this->selector->setConnections($connections);

        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[0], $connection);

        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[1], $connection);

        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[0], $connection);

        $connection = $this->selector->nextConnection();
        $this->assertEquals($connections[1], $connection);
    }
}