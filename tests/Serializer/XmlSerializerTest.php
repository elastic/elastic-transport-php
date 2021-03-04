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

use Elastic\Transport\Serializer\XmlSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleXMLElement;

final class XmlSerializerTest extends TestCase
{
    /**
     * @var string
     */
    private $xml;

    public function setUp(): void
    {   
        $this->xml = <<<'EOT'
<?xml version="1.0"?>
<document>
    <cmd>login</cmd>
    <login>Richard</login>
</document>

EOT;
    }

    public function testUnserialize()
    {
        $result = XmlSerializer::unserialize($this->xml);
        $this->assertInstanceOf(SimpleXMLElement::class, $result);
        $this->assertEquals('login', $result->cmd);
        $this->assertEquals('Richard', $result->login);
    }

    public function testSerialize()
    {
        $data = new SimpleXMLElement($this->xml);
        $result = XmlSerializer::serialize($data);
        $this->assertEquals($this->xml, $result);
    }
}