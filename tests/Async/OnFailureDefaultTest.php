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

namespace Elastic\Transport\Test\Async;

use Elastic\Transport\Async\OnFailureDefault;
use Elastic\Transport\Async\OnFailureInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class OnFailureDefaultTest extends TestCase
{
    public function setUp(): void
    {
        $this->onFailure = new OnFailureDefault();
        $this->request = $this->createMock(RequestInterface::class);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(OnFailureInterface::class, $this->onFailure);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testFailure()
    {
        $this->onFailure->failure(new Exception(), $this->request, 0);
    }
}