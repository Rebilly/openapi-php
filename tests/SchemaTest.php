<?php
/**
 * This source file is proprietary and part of Rebilly.
 *
 * (c) Rebilly SRL
 *     Rebilly Ltd.
 *     Rebilly Inc.
 *
 * @see https://www.rebilly.com
 */

declare(strict_types=1);

namespace Rebilly\OpenAPI;

use InvalidArgumentException;
use function count;

final class SchemaTest extends TestCase
{
    /**
     * @test
     */
    public function loadSchemaFromFile(): Schema
    {
        $schema = new Schema(__DIR__ . '/Doubles/openapi_3.0.1.json');
        self::assertSame('3.0.1', $schema->getVersion());

        $schema = new Schema($this->getSchemaSource());
        self::assertSame('3.0.0', $schema->getVersion());

        return $schema;
    }

    /**
     * @test
     */
    public function failOnLoadUnsupportedVersion(): void
    {
        $this->expectExceptionObject(new UnexpectedValueException('Unsupported OpenAPI Specification schema'));
        new Schema(__DIR__ . '/Doubles/openapi_3.1.0.json');
    }

    /**
     * @test
     * @depends loadSchemaFromFile
     */
    public function howToUseSwagger(Schema $schema): void
    {
        self::assertSame(['https://api.example.com/v1'], $schema->getServers());

        $definitions = $schema->getDefinitionNames();
        self::assertGreaterThan(0, count($definitions));

        foreach ($definitions as $name) {
            $definition = $schema->getDefinition($name);
            self::assertTrue(isset($definition->type));
        }

        $paths = $schema->getAvailablePaths();
        self::assertGreaterThan(0, count($paths));

        foreach ($paths as $path) {
            $path = $schema->getPathSchema($path);
            self::assertNotNull($path);
        }

        $path = '/posts';
        $methods = $schema->getAllowedMethods($path);
        self::assertSame(['OPTIONS', 'HEAD', 'GET', 'POST'], $methods);

        $consumes = $schema->getRequestContentTypes($path, 'post');
        self::assertSame(['application/json'], $consumes);

        $requestBody = $schema->getRequestBodySchema($path, 'get');
        self::assertNull($requestBody);

        $requestBody = $schema->getRequestBodySchema($path, 'post');
        self::assertNotNull($requestBody);

        $requestBody = $schema->getRequestBodySchema($path, 'post', 'application/json');
        self::assertNotNull($requestBody);

        try {
            $schema->getRequestBodySchema($path, 'post', 'application/xml');
            self::fail('Expected failure on unknown request type');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Unsupported request content type', $e->getMessage());
        }

        $requestPathParams = $schema->getRequestPathParameters($path, 'post');
        self::assertEmpty($requestPathParams);

        $requestQueryParams = $schema->getRequestQueryParameters($path, 'get');
        self::assertCount(1, $requestQueryParams);

        $produces = $schema->getResponseContentTypes($path, 'get', '200');
        self::assertSame(['application/json'], $produces);

        self::assertTrue($schema->isResponseDefined($path, 'get', '200'));
        self::assertFalse($schema->isResponseDefined($path, 'get', '201'));

        $responseHeaders = $schema->getResponseHeaderSchemas($path, 'get', '200');
        self::assertArrayHasKey('Content-Type', $responseHeaders);

        $responseBody = $schema->getResponseBodySchema($path, 'get', '200');
        self::assertNotNull($responseBody);

        $responseBody = $schema->getResponseBodySchema($path, 'get', '200', 'application/json');
        self::assertNotNull($responseBody);

        try {
            $schema->getResponseBodySchema($path, 'get', '200', 'application/xml');
            self::fail('Expected failure on unknown response type');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Unsupported response content type', $e->getMessage());
        }
    }
}
