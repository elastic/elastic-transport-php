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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

final class ConnectionTest extends TestCase
{
    public function setUp(): void
    {
        $this->connection = new Connection('localhost');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testGetUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->connection->getUri());
    }

    public function testIsAliveAfterConstructor()
    {
        $this->assertTrue($this->connection->isAlive());
    }

    public function testMarkAlive()
    {
        $this->connection->markAlive(false);
        $this->assertFalse($this->connection->isAlive());

        $this->connection->markAlive(true);
        $this->assertTrue($this->connection->isAlive());
    }
}