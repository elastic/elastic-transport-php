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

use ArrayIterator;
use Elastic\Transport\Exception\InvalidJsonException;
use JsonException;

use function explode;
use function json_decode;
use function sprintf;
use function strpos;

class NDJsonObjectSerializer extends NDJsonArraySerializer
{
    /**
     * @return ArrayIterator
     */
    public static function deserialize(string $data)
    {
        $array = explode(strpos($data, "\r\n") !== false ? "\r\n" : "\n", $data);
        $result = new ArrayIterator();
        foreach ($array as $json) {
            if (empty($json)) {
                continue;
            }
            try {
                $result[] = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidJsonException(sprintf(
                    "Not a valid NDJson: %s", 
                    $e->getMessage()
                ));
            }    
        }
        return $result;
    }
}