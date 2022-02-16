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

use Elastic\Transport\NodePool\Resurrect\NoResurrect;
use Elastic\Transport\NodePool\Node;
use PHPUnit\Framework\TestCase;

final class NoResurrectTest extends TestCase
{
    private Node $node;

    public function setUp(): void
    {
        $this->node = $this->createStub(Node::class);
    }

    public function testPingIsFalse()
    {
        $resurrect = new NoResurrect();
        $this->assertFalse($resurrect->ping($this->node));
    }
}