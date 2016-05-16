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
use Rebilly\OpenAPI\TestCase;
use Rebilly\OpenAPI\UnexpectedValueException;

/**
 * Class HeadersConstraintTest.
 */
class HeadersConstraintTest extends TestCase
{
    /**
     * @var HeadersConstraint
     */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->constraint = new HeadersConstraint(
            [
                'Content-Type' => [
                    'type' => 'string',
                    'required' => true,
                ],
                'Allow' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'X-Timestamp' => [
                    'type' => 'integer',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function passUnexpectedValue()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Array expected');

        $this->assertThat((object) [], $this->constraint);
    }

    /**
     * @test
     */
    public function assertValidHeaders()
    {
        $headers = [
            'Content-Type' => ['application/json'],
            'Allow' => ['OPTIONS', 'HEAD'],
            'X-Timestamp' => [time()],
        ];

        $this->assertThat($headers, $this->constraint);
    }

    /**
     * @test
     * @dataProvider provideInvalidHeaders
     *
     * @param array $headers
     */
    public function assertInvalidHeaders(array $headers)
    {
        $error = $this->getDataSetName();

        try {
            $this->assertThat($headers, $this->constraint);
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

    public function provideInvalidHeaders()
    {
        return [
            'Missing required header' => [
                [],
            ],
            '[Allow] There must be a minimum of 2 items in the array' => [
                [
                    'Content-Type' => ['application/json'],
                    'Allow' => ['OPTIONS'],
                ],
            ],
            '[X-Timestamp] String value found, but an integer is required' => [
                [
                    'X-Timestamp' => ['foo'],
                ],
            ],
        ];
    }
}
