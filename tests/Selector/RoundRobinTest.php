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

namespace Elastic\Transport\Test\Selector;

use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\Selector\RoundRobin;
use Elastic\Transport\Exception\NoNodeAvailableException;
use PHPUnit\Framework\TestCase;

final class RoundRobinTest extends TestCase
{
    /**
     * @var RoundRobin
     */
    private $selector;

    /**
     * @var Node
     */
    private $node;

    public function setUp(): void
    {
        $this->selector = new RoundRobin();
        $this->node = $this->createStub(Node::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetNodes()
    {
        $this->selector->setNodes([$this->node]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetEmptyNodes()
    {
        $this->selector->setNodes([]);
    }

    public function testGetNodes()
    {
        $this->assertIsArray($this->selector->getNodes());
    }

    public function testNextNodeWithEmptyNodesThrowException()
    {
        $this->expectException(NoNodeAvailableException::class);
        $connection = $this->selector->nextNode();
    }

    public function testNextNodeWithOneNode()
    {
        $nodes = [ $this->node ];
        $this->selector->setNodes($nodes);
        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[0], $node);

        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[0], $node);
    }

    public function testNextNodeWithTwoNodes()
    {
        $node2 = $this->createStub(Node::class);
        $nodes = [ $this->node, $node2 ];
        $this->selector->setNodes($nodes);

        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[0], $node);

        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[1], $node);

        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[0], $node);

        $node = $this->selector->nextNode();
        $this->assertEquals($nodes[1], $node);
    }
}