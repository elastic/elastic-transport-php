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

namespace Elastic\Transport\Test\Resurrect;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\Resurrect\NoResurrect;
use PHPUnit\Framework\TestCase;

final class NoResurrectTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    public function setUp(): void
    {
        $this->connection = $this->createStub(Connection::class);
    }

    public function testPingIsFalse()
    {
        $resurrect = new NoResurrect();
        $this->assertFalse($resurrect->ping($this->connection));
    }
}