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

namespace Elastic\Transport\Test\Serializer;

use Elastic\Transport\Serializer\TextSerializer;
use PHPUnit\Framework\TestCase;

final class TextSerializerTest extends TestCase
{
    /**
     * @var string
     */
    private $data;

    public function setUp(): void
    {
        $this->data = 'Hello World!';
    }

    public function testUnserialize()
    {
        $result = TextSerializer::unserialize($this->data);
        $this->assertEquals($this->data, $result);
    }

    public function testSerialize()
    {
        $result = TextSerializer::serialize($this->data);
        $this->assertEquals($this->data, $result);
    }
}