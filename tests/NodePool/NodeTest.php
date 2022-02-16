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

namespace Elastic\Transport\Test\ConnectionPool;

use Elastic\Transport\NodePool\Node;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

final class NodeTest extends TestCase
{
    /**
     * @var Node
     */
    private $node;

    public function setUp(): void
    {
        $this->node = new Node('localhost');
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Node::class, $this->node);
    }

    public function testGetUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->node->getUri());
    }

    public function testIsAliveAfterConstructor()
    {
        $this->assertTrue($this->node->isAlive());
    }

    public function testMarkAlive()
    {
        $this->node->markAlive(false);
        $this->assertFalse($this->node->isAlive());

        $this->node->markAlive(true);
        $this->assertTrue($this->node->isAlive());
    }
}