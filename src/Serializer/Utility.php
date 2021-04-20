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

class Utility
{
    /**
     * Remove null values form array or object
     * 
     * @param object|array $data
     * @return void
     */
    public static function removeNullValue(&$data): void
    {
        foreach ($data as $property => &$value) {
            if (is_object($value) || is_array($value)) {
                self::removeNullValue($value);
            }
            if (null === $value) {
                if (is_array($data)) {
                    unset($data[$property]);
                } 
                if (is_object($data)) {
                    unset($data->$property);
                }
            }
        }
    }
}