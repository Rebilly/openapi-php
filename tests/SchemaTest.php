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

/**
 * Class SchemaTest.
 */
final class SchemaTest extends TestCase
{
    /**
     * @test
     */
    public function useDefaultFactoryToLoadSchemaByUri()
    {
        $factory = new SchemaFactory();
        $spec = $factory->create($this->getSchemaSource());

        $this->assertInstanceOf(Schema::class, $spec);
    }

    /**
     * @test
     */
    public function createInvalidSchema()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported OpenAPI Specification schema');

        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '1.0',
                    'host' => 'localhost',
                ]
            )
        );

        $this->assertSame('localhost', $spec->getHost());
    }

    /**
     * @test
     *
     * @return Schema
     */
    public function createSchema()
    {
        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '2.0',
                    'host' => 'localhost',
                    'schemes' => ['https', 'http'],
                    'consumes' => ['application/json'],
                    'produces' => ['application/json'],
                    'basePath' => '/v1',
                    'definitions' => [
                        'Post' => [
                            'type' => 'object',
                        ],
                    ],
                    'paths' => [
                        '/posts' => [
                            'parameters' => [
                                [
                                    'name' => 'Content-Type',
                                    'in' => 'header',
                                    'required' => true,
                                    'type' => 'string',
                                ],
                            ],
                            'get' => [
                                'parameters' => [],
                                'responses' => [
                                    '200' => [
                                        'headers' => [
                                            'Content-Type' => [
                                                'required' => true,
                                                'type' => 'string',
                                            ],
                                        ],
                                    ],
                                    'default' => [],
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertSame('localhost', $spec->getHost());

        return $spec;
    }

    /**
     * @test
     * @depends createSchema
     *
     * @param Schema $spec
     */
    public function howToUseSwagger(Schema $spec)
    {
        $this->assertSame('localhost', $spec->getHost());
        $this->assertSame('/v1', $spec->getBasePath());

        $definitions = $spec->getDefinitionNames();
        $this->assertGreaterThan(0, count($definitions));

        foreach ($definitions as $name) {
            $definition = $spec->getDefinition($name);
            $this->assertTrue(isset($definition->type));
        }

        $paths = $spec->getAvailablePaths();
        $this->assertGreaterThan(0, count($paths));

        foreach ($paths as $template) {
            $path = $spec->getPathSchema($template);
            $this->assertNotNull($path);
        }

        $template = reset($paths);

        $methods = $spec->getAllowedMethods($template);
        $this->assertTrue(in_array('OPTIONS', $methods));
        $this->assertTrue(in_array('HEAD', $methods));
        $this->assertTrue(in_array('GET', $methods));

        $schemes = $spec->getSupportedSchemes($template, 'get');
        $this->assertSame(['https', 'http'], $schemes);

        $consumes = $spec->getRequestContentTypes($template, 'get');
        $this->assertSame(['application/json'], $consumes);

        $requestHeaders = $spec->getRequestHeaderSchemas($template, 'get');
        $this->assertTrue(isset($requestHeaders['Content-Type']));

        $requestBody = $spec->getRequestBodySchema($template, 'get');
        $this->assertNull($requestBody);

        $requestPathParams = $spec->getRequestPathParameters($template, 'get');
        $this->assertEmpty($requestPathParams);

        $requestQueryParams = $spec->getRequestQueryParameters($template, 'get');
        $this->assertEmpty($requestQueryParams);

        $produces = $spec->getResponseContentTypes($template, 'get');
        $this->assertSame(['application/json'], $produces);

        $statuses = $spec->getResponseCodes($template, 'get');
        $this->assertSame(['200'], $statuses);

        $responseHeaders = $spec->getResponseHeaderSchemas($template, 'get', 200);
        $this->assertTrue(isset($responseHeaders['Content-Type']));

        $responseBody = $spec->getResponseBodySchema($template, 'get', 200);
        $this->assertNull($responseBody);
    }

    /**
     * @test
     */
    public function shouldGetUnionOfParameters()
    {
        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '2.0',
                    'host' => 'localhost',
                    'paths' => [
                        '/posts' => [
                            'parameters' => [
                                [
                                    'name' => 'foo',
                                    'in' => 'header',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'bar',
                                    'in' => 'header',
                                ],
                            ],
                            'get' => [
                                'parameters' => [
                                    [
                                        'name' => 'baz',
                                        'in' => 'header',
                                    ],
                                    [
                                        'name' => 'foo',
                                        'in' => 'header',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $parameters = $spec->getRequestHeaderSchemas('/posts', 'get');

        $this->assertCount(3, $parameters);
        $this->assertArrayHasKey('foo', $parameters);
        $this->assertArrayHasKey('bar', $parameters);
        $this->assertArrayHasKey('baz', $parameters);
        $this->assertSame(true, $parameters['foo']->required);
    }

    /**
     * @test
     */
    public function shouldGetExceptionOnMultipleBodyDeclaration()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Multiple body parameters found');

        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '2.0',
                    'host' => 'localhost',
                    'paths' => [
                        '/posts' => [
                            'parameters' => [
                                [
                                    'name' => 'foo',
                                    'in' => 'body',
                                ],
                                [
                                    'name' => 'bar',
                                    'in' => 'body',
                                ],
                            ],
                            'get' => [],
                        ],
                    ],
                ]
            )
        );

        $spec->getRequestBodySchema('/posts', 'get');
    }

    /**
     * @test
     */
    public function shouldGetExceptionOnMultipleParametersDeclaration()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Multiple parameters found');

        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '2.0',
                    'host' => 'localhost',
                    'paths' => [
                        '/posts' => [
                            'parameters' => [
                                [
                                    'name' => 'foo',
                                    'in' => 'header',
                                ],
                                [
                                    'name' => 'foo',
                                    'in' => 'header',
                                ],
                            ],
                            'get' => [],
                        ],
                    ],
                ]
            )
        );

        $spec->getRequestHeaderSchemas('/posts', 'get');
    }

    /**
     * @test
     */
    public function shouldGetExceptionOnInvalidRequestBodyDeclaration()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid body parameter definition');

        $spec = new Schema(
            $this->createObject(
                [
                    'swagger' => '2.0',
                    'host' => 'localhost',
                    'paths' => [
                        '/posts' => [
                            'parameters' => [
                                [
                                    'name' => 'foo',
                                    'in' => 'body',
                                ],
                            ],
                            'get' => [],
                        ],
                    ],
                ]
            )
        );

        $spec->getRequestBodySchema('/posts', 'get');
    }
}
