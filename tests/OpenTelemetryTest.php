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

use Elastic\Transport\Exception\InvalidArgumentException;
use Elastic\Transport\OpenTelemetry;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryTest extends TestCase
{
    public function tearDown(): void
    {
        // Remove env variables
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY);
    }

    public function testConstructWithNoParams()
    {
        $otel = new OpenTelemetry();
        $this->assertInstanceOf(OpenTelemetry::class, $otel);
    }

    public function testConstructWithEnvBodyStrategy()
    {
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        $otel = new OpenTelemetry();
        $this->assertInstanceOf(OpenTelemetry::class, $otel);
    }

    public function testConstructWithInvalidEnvBodyStrategy()
    {
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=foo');
        $this->expectException(InvalidArgumentException::class);
        $otel = new OpenTelemetry();
    }

    public function testProcessBodyWithDefault()
    {
        $body = '{"password":"supersecret"}';
        $otel = new OpenTelemetry();
        // Default is omit, it should returns an empty string
        $this->assertEmpty($otel->processBody($body, 'search'));
    }

    public function testProcessBodyWithRaw()
    {
        $body = '{"password":"supersecret"}';
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=raw');
        $otel = new OpenTelemetry();
        // Default is omit, it should returns an empty string
        $this->assertEquals($body, $otel->processBody($body, 'search'));
    }

    public function testProcessBodyWithSanitize()
    {
        $body = '{"password":"supersecret"}';
        $sanitizeBody = sprintf('{"password":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        $otel = new OpenTelemetry();
        // Default is omit, it should returns an empty string
        $this->assertEquals($sanitizeBody, $otel->processBody($body));
    }

    public function testProcessBodyWithSanitizeUsingRegex()
    {
        $body = '{"secret_key":"supersecret"}';
        $sanitizeBody = sprintf('{"secret_key":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        $otel = new OpenTelemetry();
        // Default is omit, it should returns an empty string
        $this->assertEquals($sanitizeBody, $otel->processBody($body));
    }

    public function testProcessBodyWithSanitizeUsingCustomRegex()
    {
        $body = '{"foo_bar":"supersecret"}';
        $sanitizeBody = sprintf('{"foo_bar":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_SANITIZE_KEYS . '=bar');
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        $otel = new OpenTelemetry();
        // Default is omit, it should returns an empty string
        $this->assertEquals($sanitizeBody, $otel->processBody($body));
    }
}