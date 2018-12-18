<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI\PhpUnit;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Rebilly\OpenAPI\Schema;
use Rebilly\OpenAPI\TestCase;
use stdClass;

final class AssertsTest extends TestCase
{
    use Asserts;

    /** @var Schema */
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = $this->getSchema();
    }

    /**
     * @test
     * @dataProvider provideValidRequests
     */
    public function validateValidRequest(string $path, Request $request): void
    {
        $this->assertRequest($this->schema, $path, $request);
    }

    /**
     * @test
     * @dataProvider provideInvalidRequests
     */
    public function validateInvalidRequest(string $path, Request $request): void
    {
        $error = $this->getDataSetName();

        try {
            $this->assertRequest($this->schema, $path, $request);
        } catch (Exception $e) {
            $this->assertContains(
                $error,
                $e->getMessage(),
                "Failed asserting that passed invalid URI: {$error}"
            );
        }

        if (!isset($e)) {
            $this->fail('Failed asserting that passed invalid URI');
        }
    }

    /**
     * @test
     * @dataProvider provideValidResponses
     */
    public function validateValidResponse(string $path, string $method, Response $response): void
    {
        $this->assertResponse($this->schema, $path, $method, $response);
    }

    /**
     * @test
     * @dataProvider provideValidObjects
     */
    public function validateValidDefinition(string $class, stdClass $object): void
    {
        $this->assertDefinitionSchema($this->schema, $class, $object);
    }

    public function provideValidRequests(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $body = json_encode(
            [
                'id' => 'foo',
                'title' => 'Hello world!',
                'body' => 'Hello world!',
                'author' => [
                    'name' => 'John Dou',
                    'email' => 'john.dou@mail.com',
                ],
            ]
        );

        return [
            [
                '/posts/{id}',
                new Request('GET', 'https://api.example.com/v1/posts/foo/', $headers),
            ],
            [
                '/posts',
                new Request('POST', 'https://api.example.com/v1/posts/', $headers, $body),
            ],
        ];
    }

    public function provideInvalidRequests(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $post = [
            'title' => 'Hello world',
            'body' => 'Hi there',
            'author' => ['name' => 'John Doe', 'email' => 'john@doe.com'],
        ];

        return [
            'Failed asserting that \'POST\' matches an allowed methods' => [
                '/posts/{id}',
                new Request('POST', 'https://api.example.com/v1/posts/foo/', $headers),
            ],
            'Failed asserting that \'text/json\' is an allowed content-type' => [
                '/posts',
                new Request('POST', 'https://api.example.com/v1/posts', ['Content-Type' => 'text/json'], json_encode($post)),
            ],
        ];
    }

    public function provideValidResponses(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $body = json_encode(
            [
                'id' => 'foo',
                'title' => 'Hello world!',
                'body' => 'Hello world!',
                'author' => [
                    'name' => 'John Dou',
                    'email' => 'john.dou@mail.com',
                ],
            ]
        );

        return [
            [
                '/posts/{id}',
                'GET',
                new Response(200, $headers, $body),
            ],
            [
                '/posts/{id}',
                'OPTIONS',
                new Response(204, $headers + ['Allow' => 'OPTIONS, HEAD, GET']),
            ],
            [
                '/posts',
                'POST',
                new Response(201, $headers, $body),
            ],
        ];
    }

    public function provideValidObjects(): array
    {
        return [
            [
                'Post',
                $this->createObject(
                    [
                        'id' => 'foo',
                        'title' => 'Hello world!',
                        'body' => 'Hello world!',
                        'author' => [
                            'name' => 'John Dou',
                            'email' => 'john.dou@mail.com',
                        ],
                    ]
                ),
            ],
        ];
    }
}
