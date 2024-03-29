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

namespace Rebilly\OpenAPI\PhpUnit;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Rebilly\OpenAPI\TestCase;
use Rebilly\OpenAPI\UnexpectedValueException;

class UriConstraintTest extends TestCase
{
    /** @var UriConstraint */
    private $constraint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new UriConstraint(
            ['https://api.example.com/v1'],
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
    public function assertValidUri(): void
    {
        $uri = new Uri('https://api.example.com/v1/posts/1?q=');
        self::assertThat($uri, $this->constraint);
    }

    /**
     * @test
     */
    public function passUnexpectedInstance(): void
    {
        $this->expectExceptionObject(new UnexpectedValueException('The object should implements UriInterface'));
        $uri = 'https://api.example.com/v1/posts';
        self::assertThat($uri, $this->constraint);
    }

    /**
     * @test
     * @dataProvider provideInvalidUri
     */
    public function assertInvalidJson(Uri $uri): void
    {
        $error = $this->getDataSetName();

        try {
            self::assertThat($uri, $this->constraint);
        } catch (Exception $e) {
            self::assertStringContainsString(
                $error,
                $e->getMessage(),
                "Failed asserting that passed invalid URI: {$error}"
            );
        }

        if (!isset($e)) {
            self::fail('Failed asserting that passed invalid URI');
        }
    }

    public function provideInvalidUri(): array
    {
        return [
            'Unexpected URL, does not found in defined servers' => [
                new Uri('https://api.example.com/v2/posts'),
            ],
            'Unexpected URI path, does not match the template' => [
                new Uri('https://api.example.com/v1/posts/1/comments'),
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
