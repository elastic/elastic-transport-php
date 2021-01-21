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

namespace Elastic\Transport\Test;

use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\Exception\CloudIdParseException;
use Elastic\Transport\Transport;
use Elastic\Transport\TransportBuilder;
use Psr\Http\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

final class TransportBuilderTest extends TestCase
{
    private $client;
    private $connectionPool;
    private $logger;
    private $builder;

    public function setUp(): void
    {
        $this->client = $this->createStub(ClientInterface::class);
        $this->connectionPool = $this->createStub(SimpleConnectionPool::class);
        $this->logger = new TestLogger();
        $this->builder = TransportBuilder::create();
    }

    public function testCreate()
    {
        $this->assertInstanceOf(TransportBuilder::class, $this->builder);
    }

    public function testSetClient()
    {
        $result = $this->builder->setClient($this->client);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->builder, $result);
    }

    public function testSetConnectionPool()
    {
        $result = $this->builder->setConnectionPool($this->connectionPool);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->builder, $result);
    }

    public function testSetLogger()
    {
        $result = $this->builder->setLogger($this->logger);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($this->builder, $result);
    }

    public function testSetHosts()
    {
        $hosts = ['xxx', 'yyy'];
        $result = $this->builder->setHosts($hosts);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($hosts, $this->builder->getHosts());
    }

    public function testSetEmptyHosts()
    {
        $hosts = [];
        $result = $this->builder->setHosts($hosts);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($hosts, $this->builder->getHosts());
    }

    public function testSetHeaders()
    {
        $headers = [
            'Authorization' => 'ApiKey xxx'
        ];
        $result = $this->builder->setHeaders($headers);

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertEquals($headers, $this->builder->getHeaders());
    }

    public function testSetInvalidCloudId()
    {
        $this->expectException(CloudIdParseException::class);
        $this->builder->setCloudId('xxx');
    }

    public function testSetValidCloudId()
    {
        $result = $this->builder->setCloudId(sprintf(
            "xxx:%s", 
            base64_encode('aaa$cloud.elastic.co:1234567890')
        ));

        $this->assertInstanceOf(TransportBuilder::class, $result);
        $this->assertContains('https://cloud.elastic.co.aaa', $this->builder->getHosts());
    }

    public function testSetUserInfoWithOnlyUser()
    {
        $result = $this->builder->setUserInfo('user');
        $this->assertInstanceOf(TransportBuilder::class, $result);
    }

    public function testSetUserInfoWithUserAndPassword()
    {
        $result = $this->builder->setUserInfo('user', 'password');
        $this->assertInstanceOf(TransportBuilder::class, $result);
    }

    public function testBuildWithDefaultSettings()
    {
        $this->assertInstanceOf(Transport::class, $this->builder->build());
    }

    public function testBuildWithUserSettings()
    {
        $this->builder->setUserInfo('user');
        $this->assertInstanceOf(Transport::class, $this->builder->build());
    }

    public function testBuildWithUserAndPasswordSettings()
    {
        $this->builder->setUserInfo('user', 'password');
        $this->assertInstanceOf(Transport::class, $this->builder->build());
    }

    public function testBuildWithHeaders()
    {
        $this->builder->setHeaders(['foo' => 'bar']);
        $this->assertInstanceOf(Transport::class, $this->builder->build());
    }
}