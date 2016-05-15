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

/**
 * Class AssertsTest.
 */
final class AssertsTest extends TestCase
{
    use Asserts;

    /**
     * @var Schema
     */
    private $spec;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->spec = $this->getSchemaFactory()->create($this->getSchemaSource());
    }

    /**
     * @test
     * @dataProvider provideValidRequests
     *
     * @param string $template
     * @param Request $request
     */
    public function validateValidRequest($template, Request $request)
    {
        $this->assertRequest($this->spec, $template, $request);
    }

    /**
     * @test
     * @dataProvider provideInvalidRequests
     *
     * @param string $template
     * @param Request $request
     */
    public function validateInvalidRequest($template, Request $request)
    {
        $error = $this->getDataSetName();

        try {
            $this->assertRequest($this->spec, $template, $request);
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
     *
     * @param string $template
     * @param string $method
     * @param Response $response
     */
    public function validateValidResponse($template, $method, Response $response)
    {
        $this->assertResponse($this->spec, $template, $method, $response);
    }

    /**
     * @return array
     */
    public function provideValidRequests()
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
                new Request('GET', 'http://api.example.com/v1/posts/foo/', $headers),
            ],
            [
                '/posts',
                new Request('POST', 'http://api.example.com/v1/posts/', $headers, $body),
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideInvalidRequests()
    {
        $headers = ['Content-Type' => 'application/json'];

        return [
            'Failed asserting that \'POST\' matches an allowed methods' => [
                '/posts/{id}',
                new Request('POST', 'http://api.example.com/v1/posts/foo/', $headers),
            ],
            'Failed asserting that \'text/json\' is an allowed content-type' => [
                '/posts/{id}',
                new Request('GET', 'http://api.example.com/v1/posts/foo/', ['Content-Type' => 'text/json']),
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideValidResponses()
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
}
