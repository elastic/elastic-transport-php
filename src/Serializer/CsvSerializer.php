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

namespace Elastic\Transport\Serializer;

use function explode;
use function str_getcsv;
use function substr;

class CsvSerializer implements SerializerInterface
{
    public static function serialize($data): string
    {
        $result = '';
        foreach ($data as $row) {
            $result .= implode(',', $row) . "\n";
        }
        return empty($result) ? $result : substr($result, 0, -1);
    }

    /**
     * @return array
     */
    public static function deserialize(string $data)
    {
        $result = [];
        foreach (explode("\n", $data) as $row) {
            $result[] = str_getcsv($row);
        }
        return $result;
    }
}