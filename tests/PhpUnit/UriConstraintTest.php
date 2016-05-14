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
use GuzzleHttp\Psr7\Uri;
use Rebilly\OpenAPI\TestCase;
use Rebilly\OpenAPI\UnexpectedValueException;

/**
 * Class UriConstraintTest.
 */
class UriConstraintTest extends TestCase
{
    /**
     * @var UriConstraint
     */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->constraint = new UriConstraint(
            ['https'],
            'api.example.com',
            '/v1',
            '/posts/{id}',
            [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
            [
                'q' => [
                    'type' => 'string',
                    'required' => true,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function assertValidUri()
    {
        $uri = new Uri('https://api.example.com/v1/posts/1?q=');

        $this->assertThat($uri, $this->constraint);
    }

    /**
     * @test
     */
    public function passUnexpectedInstance()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The object should implements UriInterface');

        $uri = 'https://api.example.com/v1/posts';

        $this->assertThat($uri, $this->constraint);
    }

    /**
     * @test
     * @dataProvider provideInvalidUri
     *
     * @param Uri $uri
     */
    public function assertInvalidJson(Uri $uri)
    {
        $error = $this->getDataSetName();

        try {
            $this->assertThat($uri, $this->constraint);
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

    public function provideInvalidUri()
    {
        return [
            'Unsupported scheme' => [
                new Uri('http://api.example.com/v1/posts'),
            ],
            'Unexpected host' => [
                new Uri('https://example.com/v1/posts'),
            ],
            'Unexpected base path' => [
                new Uri('https://api.example.com/v2/posts'),
            ],
            'Missing path segment' => [
                new Uri('https://api.example.com/v1/comments/1'),
            ],
            '[path] String value found, but an integer is required' => [
                new Uri('https://api.example.com/v1/posts/foo'),
            ],
            'Missing required query param' => [
                new Uri('https://api.example.com/v1/posts/1'),
            ],
            '[query] Integer value found, but a string is required' => [
                new Uri('https://api.example.com/v1/posts/1?q=1'),
            ],
        ];
    }
}
