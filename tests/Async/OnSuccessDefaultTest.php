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

use Elastic\Transport\Async\OnSuccessDefault;
use Elastic\Transport\Async\OnSuccessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class OnSuccessDefaultTest extends TestCase
{
    private OnSuccessInterface $onSuccess;
    private MockObject|ResponseInterface $response;
    
    public function setUp(): void
    {
        $this->onSuccess = new OnSuccessDefault();
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(OnSuccessInterface::class, $this->onSuccess);
    }

    public function testSuccess()
    {
        $result = $this->onSuccess->success($this->response, 0);
        $this->assertEquals($this->response, $result);
    }
}