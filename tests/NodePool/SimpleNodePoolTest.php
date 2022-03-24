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

namespace Elastic\Transport\Test\NodePool;

use Elastic\Transport\NodePool\Resurrect\ResurrectInterface;
use Elastic\Transport\NodePool\Selector\SelectorInterface;
use Elastic\Transport\NodePool\SimpleNodePool;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\Resurrect\NoResurrect;
use Elastic\Transport\NodePool\Selector\RoundRobin;
use PHPUnit\Framework\TestCase;

final class SimpleNodePoolTest extends TestCase
{
    /**
     * @var SelectorInterface
     */
    private $selector;

    /**
     * @var ResurrectInterface
     */
    private $resurrect;

    public function setUp(): void
    {
        $this->selector = $this->createStub(SelectorInterface::class);
        $this->resurrect = $this->createStub(ResurrectInterface::class);
        $this->nodePool = new SimpleNodePool($this->selector, $this->resurrect);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(SimpleNodePool::class, $this->nodePool);
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
        $this->nodePool->setHosts($hosts);
    }

    public function testNextNodeWithRoundRobinAndNoResurrect()
    {
        $hosts = [
            '192.168.1.10',
            '192.168.1.11',
        ];
        $this->nodePool = new SimpleNodePool(
            new RoundRobin(),
            new NoResurrect()
        );
        $this->nodePool->setHosts($hosts);

        $node = $this->nodePool->nextNode();
        $this->assertInstanceOf(Node::class, $node);
        $host1 = $node->getUri()->getHost();
        $this->assertContains($host1, $hosts);

        $node2 = $this->nodePool->nextNode();
        $this->assertInstanceOf(Node::class, $node2);
        $host2 = $node2->getUri()->getHost();
        $this->assertContains($host1, $hosts);
        $this->assertNotEquals($host1, $host2);

        $node3 = $this->nodePool->nextNode();
        $this->assertInstanceOf(Node::class, $node3);
        $this->assertEquals($host1, $node3->getUri()->getHost());

        $node4 = $this->nodePool->nextNode();
        $this->assertInstanceOf(Node::class, $node4);
        $this->assertEquals($host2, $node4->getUri()->getHost());
    }

    public function testNextConnectionWithMockSelectorWithDeadNodes()
    {
        $hosts = [
            '192.168.1.10',
            '192.168.1.11',
        ];

        $node = $this->createStub(Node::class);
        $node->method('isAlive')
            ->will($this->onConsecutiveCalls(false, false));

        $this->selector->method('nextNode')
            ->willReturn($node);

        $NodePool = new SimpleNodePool($this->selector, $this->resurrect);
        $NodePool->setHosts($hosts);

        $this->expectException(NoNodeAvailableException::class);
        $this->expectExceptionMessage(sprintf(
            "No alive nodes. All the %d nodes seem to be down.",
            count($hosts)
        ));
        $node = $NodePool->nextNode();
    }
}