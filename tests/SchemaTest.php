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

final class SchemaTest extends TestCase
{
    /**
     * @test
     */
    public function useDefaultFactoryToLoadSchemaByUri(): Schema
    {
        $schema = new Schema($this->getSchemaSource());
        $this->assertSame('3.0.0', $schema->getVersion());

        return $schema;
    }

    /**
     * @test
     * @depends useDefaultFactoryToLoadSchemaByUri
     */
    public function howToUseSwagger(Schema $schema): void
    {
        $this->assertEquals(['https://api.example.com/v1'], $schema->getServers());

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

        $requestPathParams = $schema->getRequestPathParameters($path, 'post');
        $this->assertEmpty($requestPathParams);

        $requestQueryParams = $schema->getRequestQueryParameters($path, 'get');
        $this->assertCount(1, $requestQueryParams);

        $produces = $schema->getResponseContentTypes($path, 'get', '200');
        $this->assertSame(['application/json'], $produces);

        $statuses = $schema->getResponseCodes($path, 'get');
        $this->assertSame([200], $statuses);

        $responseHeaders = $schema->getResponseHeaderSchemas($path, 'get', 200);
        $this->assertArrayHasKey('Content-Type', $responseHeaders);

        $responseBody = $schema->getResponseBodySchema($path, 'get', '200');
        $this->assertNotNull($responseBody);
    }
}
