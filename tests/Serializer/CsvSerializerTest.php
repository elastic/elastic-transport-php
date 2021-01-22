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

use Elastic\Transport\Serializer\CsvSerializer;
use PHPUnit\Framework\TestCase;

final class CsvSerializerTest extends TestCase
{
    public function testDeserialize()
    {
        $csv = <<<'EOT'
1,2,3
4,5,6
EOT;

        $result = CsvSerializer::deserialize($csv);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals([1,2,3], $result[0]);
        $this->assertEquals([4,5,6], $result[1]);
    }

    public function testSerialize()
    {
        $csv = <<<'EOT'
1,2,3
4,5,6
EOT;
        $data = [
            [1,2,3],
            [4,5,6]
        ];
        $result = CsvSerializer::serialize($data);
        $this->assertEquals($csv, $result);
    }

    public function testSerializeEmptyCsv()
    {
        $csv = '';
        $data = [];
        $result = CsvSerializer::serialize($data);
        $this->assertEquals($csv, $result);
    }
}