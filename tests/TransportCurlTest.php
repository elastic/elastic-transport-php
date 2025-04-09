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

namespace Elastic\Transport\Test;

use Elastic\Transport\Client\Curl;
use Elastic\Transport\Transport;
use Elastic\Transport\TransportBuilder;
use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;

final class TransportCurlTest extends TestCase
{
    private RequestFactoryInterface $requestFactory;

    private TransportBuilder $builder;
    /**
     * @var array<DiscoveryStrategy>
     */
    private static array $strategies;

    public static function setUpBeforeClass(): void
    {
        self::$strategies = Psr18ClientDiscovery::getStrategies();
        Psr18ClientDiscovery::setStrategies([]);
    }

    public static function tearDownAfterClass(): void
    {
        Psr18ClientDiscovery::setStrategies(self::$strategies);
    }

    public function setUp(): void
    {
        Psr18ClientDiscovery::clearCache();
        Psr18ClientDiscovery::setStrategies([]);
        $this->builder = TransportBuilder::create();
    }

    public function testDefaultClientIsCurl()
    {
        $transport = $this->builder->build();

        $this->assertInstanceOf(Curl::class, $transport->getClient());
    }

    public function testSetElasticMetaHeaderContainsCurl()
    {
        $transport = $this->builder->build();
        $transport->setElasticMetaHeader('test', '1.0.0');
        
        $request = new Request('GET', 'http://localhost:8100');
        try {
            $response = $transport->sendRequest($request);
        } catch (Exception $e) {

        }
        $request = $transport->getLastRequest();
        $meta = $request->getHeader(Transport::ELASTIC_META_HEADER);
        $this->assertStringContainsString('test=1.0.0', $meta[0]);
        $this->assertStringContainsString(sprintf('ec=%s', Transport::VERSION), $meta[0]);
    }
}