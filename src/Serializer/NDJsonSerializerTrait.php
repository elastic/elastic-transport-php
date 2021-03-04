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

use Elastic\Transport\Exception\InvalidJsonException;
use JsonException;

use function json_encode;
use function sprintf;

trait NDJsonSerializerTrait
{
    public static function serialize($rows): string
    {
        $result = '';
        foreach ($rows as $row) {
            if (empty($row)) {
                $result .= "{}\n";
                continue;
            }
            try {
                $result .= json_encode($row, JSON_PRESERVE_ZERO_FRACTION + JSON_INVALID_UTF8_SUBSTITUTE + JSON_THROW_ON_ERROR) . "\n";
            } catch (JsonException $e) {
                throw new InvalidJsonException(sprintf(
                    "I cannot serialize to NDJson: %s", 
                    $e->getMessage()
                ));
            }
        }
        return $result;
    }
}