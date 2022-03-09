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

use Elastic\Transport\Serializer\JsonSerializer;
use PHPUnit\Framework\TestCase;

final class JsonStringSerializerTest extends TestCase
{
    public function testSerialize()
    {
        $json = '{"fruit":"Apple","size":"Large","color":"Red"}';
        $data = $json;
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }

    public function testSerializeEmptyString()
    {
        $json = '{}';
        $data = '';
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }
}