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

use Elastic\Transport\Serializer\Utility;
use PHPUnit\Framework\TestCase;
use stdClass;

final class UtilitySerializerTest extends TestCase
{
    public static function getTestCaseWithArray()
    {
        return [
            [ 
                [
                    'a' => 1, 
                    'b' => 2, 
                    'c' => null
                ],
                [
                    'a' => 1, 
                    'b' => 2, 
                ]
            ],
            [ 
                [
                    'a' => 1, 
                    'b' => null, 
                    'c' => [
                        'd' => 3,
                        'e' => null
                    ]
                ],
                [
                    'a' => 1, 
                    'c' => [
                        'd' => 3
                    ]
                ]
            ],
            [ 
                [
                    'a' => 1, 
                    'b' => null, 
                    'c' => [
                        'd' => 3,
                        'e' => null,
                        'f' => [
                            'g' => null
                        ]
                    ]
                ],
                [
                    'a' => 1, 
                    'c' => [
                        'd' => 3,
                        'f' => []
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getTestCaseWithArray
     */
    public function testRemoveNullValueWithArray(array $before, array $after)
    {
        Utility::removeNullValue($before);
        $this->assertEquals($after, $before);
    }

    public static function getTestCaseWithObject()
    {
        $obj1 = new stdClass;
        $obj1->a = 1;
        $obj1->b = null;

        $obj1A = clone($obj1);
        unset($obj1A->b);

        $obj2 = new stdClass;
        $obj2->a = 1;
        $obj2->b = new stdClass;
        $obj2->b->c = 2;
        $obj2->b->d = null;

        $obj2A = clone($obj2);
        unset($obj2A->b->d);

        $obj3 = new stdClass;
        $obj3->a = 1;
        $obj3->b = new stdClass;
        $obj3->b->c = 2;
        $obj3->b->d = new stdClass;
        $obj3->b->d->f = null;

        $obj3A = clone($obj3);
        unset($obj3A->b->d->f);

        return [
            [ $obj1, $obj1A ],
            [ $obj2, $obj2A ],
            [ $obj3, $obj3A ]
        ];
    }

    /**
     * @dataProvider getTestCaseWithObject
     */
    public function testRemoveNullValueWithObject(object $before, object $after)
    {
        Utility::removeNullValue($before);
        $this->assertEquals($after, $before);
    }

    public static function getTestCaseWithMixedValues()
    {
        $obj1 = new stdClass;
        $obj1->a = 1;
        $obj1->b = new stdClass;
        $obj1->b->c = 2;
        $obj1->b->d = null;

        $obj1A = clone($obj1);
        unset($obj1A->b->d);

        $obj2 = clone($obj1);
        $obj2->b->e = [
            'f' => 1,
            'g' => null
        ];
        $obj2A = clone($obj2);
        unset($obj2A->b->d);
        unset($obj2A->b->e['g']);

        return [
            [ 
                [
                    'a' => 1, 
                    'b' => 2, 
                    'c' => null,
                    'd' => $obj1
                ],
                [
                    'a' => 1, 
                    'b' => 2, 
                    'd' => $obj1A
                ]
            ],
            [   
                $obj2,
                $obj2A
            ]
        ];
    }

    /**
     * @param array|object $before
     * @param array|object $after
     * @dataProvider getTestCaseWithMixedValues
     */
    public function testRemoveNullValueWithMixedValues($before, $after)
    {
        Utility::removeNullValue($before);
        $this->assertEquals($after, $before);
    }
}