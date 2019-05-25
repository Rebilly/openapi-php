<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI;

use InvalidArgumentException;

final class SchemaTest extends TestCase
{
    /**
     * @test
     */
    public function loadSchemaFromFile(): Schema
    {
        $schema = new Schema(__DIR__ . '/Doubles/openapi_3.0.1.json');
        $this->assertSame('3.0.1', $schema->getVersion());

        $schema = new Schema($this->getSchemaSource());
        $this->assertSame('3.0.0', $schema->getVersion());

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
        $this->assertSame(['https://api.example.com/v1'], $schema->getServers());

        $definitions = $schema->getDefinitionNames();
        $this->assertGreaterThan(0, count($definitions));

        foreach ($definitions as $name) {
            $definition = $schema->getDefinition($name);
            $this->assertTrue(isset($definition->type));
        }

        $paths = $schema->getAvailablePaths();
        $this->assertGreaterThan(0, count($paths));

        foreach ($paths as $path) {
            $path = $schema->getPathSchema($path);
            $this->assertNotNull($path);
        }

        $path = '/posts';
        $methods = $schema->getAllowedMethods($path);
        $this->assertSame(['OPTIONS', 'HEAD', 'GET', 'POST'], $methods);

        $consumes = $schema->getRequestContentTypes($path, 'post');
        $this->assertSame(['application/json'], $consumes);

        $requestBody = $schema->getRequestBodySchema($path, 'get');
        $this->assertNull($requestBody);

        $requestBody = $schema->getRequestBodySchema($path, 'post');
        $this->assertNotNull($requestBody);

        $requestBody = $schema->getRequestBodySchema($path, 'post', 'application/json');
        $this->assertNotNull($requestBody);

        try {
            $schema->getRequestBodySchema($path, 'post', 'application/xml');
            $this->fail('Expected failure on unknown request type');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Unsupported request content type', $e->getMessage());
        }

        $requestPathParams = $schema->getRequestPathParameters($path, 'post');
        $this->assertEmpty($requestPathParams);

        $requestQueryParams = $schema->getRequestQueryParameters($path, 'get');
        $this->assertCount(1, $requestQueryParams);

        $produces = $schema->getResponseContentTypes($path, 'get', '200');
        $this->assertSame(['application/json'], $produces);

        $this->assertTrue($schema->isResponseDefined($path, 'get', '200'));
        $this->assertFalse($schema->isResponseDefined($path, 'get', '201'));

        $responseHeaders = $schema->getResponseHeaderSchemas($path, 'get', 200);
        $this->assertArrayHasKey('Content-Type', $responseHeaders);

        $responseBody = $schema->getResponseBodySchema($path, 'get', '200');
        $this->assertNotNull($responseBody);

        $responseBody = $schema->getResponseBodySchema($path, 'get', '200', 'application/json');
        $this->assertNotNull($responseBody);

        try {
            $schema->getResponseBodySchema($path, 'get', '200', 'application/xml');
            $this->fail('Expected failure on unknown response type');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Unsupported response content type', $e->getMessage());
        }
    }
}
