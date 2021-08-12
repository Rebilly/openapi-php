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

use PHPUnit\Framework\ExpectationFailedException;
use Rebilly\OpenAPI\TestCase;
use function date;

final class JsonSchemaConstraintTest extends TestCase
{
    /** @var JsonSchemaConstraint */
    private $constraint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new JsonSchemaConstraint(
            $this->createObject(
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['Cat', 'Dog']],
                        'description' => ['type' => 'string', 'pattern' => '[\d\w]{5,}'],
                        'birthday' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                    'required' => ['type', 'description'],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function assertValidJson(): void
    {
        $json = $this->createObject(
            [
                'type' => 'Cat',
                'description' => 'Awesome kitty',
                'birthday' => date('c'),
            ]
        );

        self::assertThat($json, $this->constraint);
    }

    /**
     * @test
     * @dataProvider provideInvalidJson
     */
    public function assertInvalidJson(array $payload): void
    {
        $json = $this->createObject($payload);
        $error = $this->getDataSetName();

        try {
            self::assertThat($json, $this->constraint);
        } catch (ExpectationFailedException $e) {
            self::assertStringContainsString(
                $error,
                $e->getMessage(),
                "Failed asserting that passed invalid JSON: {$error}"
            );
        }

        if (!isset($e)) {
            self::fail('Failed asserting that passed invalid JSON');
        }
    }

    public function provideInvalidJson(): array
    {
        return [
            'The property description is required' => [
                ['type' => 'Cat'],
            ],
            'Does not have a value in the enumeration' => [
                ['type' => 'Kitty', 'description' => 'Awesome kitty'],
            ],
            'Does not match the regex pattern' => [
                ['type' => 'Cat', 'description' => 'meow'],
            ],
            'Invalid date-time' => [
                ['type' => 'Cat', 'description' => 'Awesome kitty', 'birthday' => 'Yesterday'],
            ],
        ];
    }
}
