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

namespace Elastic\Transport\Test\Serializer;

use Elastic\Transport\Serializer\TextSerializer;
use PHPUnit\Framework\TestCase;

final class TextSerializerTest extends TestCase
{
    public function setUp(): void
    {
        $this->serializer = new TextSerializer();
        $this->data = 'Hello World!';
    }

    public function testDeserialize()
    {
        $result = $this->serializer->deserialize($this->data);
        $this->assertEquals($this->data, $result);
    }

    public function testSerialize()
    {
        $result = $this->serializer->serialize($this->data);
        $this->assertEquals($this->data, $result);
    }
}