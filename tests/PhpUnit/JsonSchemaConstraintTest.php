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

use PHPUnit_Framework_ExpectationFailedException as Exception;
use Rebilly\OpenAPI\TestCase;

/**
 * Class JsonSchemaConstraintTest.
 */
final class JsonSchemaConstraintTest extends TestCase
{
    /**
     * @var JsonSchemaConstraint
     */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
    public function assertValidJson()
    {
        $json = $this->createObject(
            [
                'type' => 'Cat',
                'description' => 'Awesome kitty',
                'birthday' => date('c'),
            ]
        );

        $this->assertThat($json, $this->constraint);
    }

    /**
     * @test
     * @dataProvider provideInvalidJson
     *
     * @param array $payload
     */
    public function assertInvalidJson(array $payload)
    {
        $json = $this->createObject($payload);
        $error = $this->getDataSetName();

        try {
            $this->assertThat($json, $this->constraint);
        } catch (Exception $e) {
            $this->assertContains(
                $error,
                $e->getMessage(),
                "Failed asserting that passed invalid JSON: {$error}"
            );
        }

        if (!isset($e)) {
            $this->fail('Failed asserting that passed invalid JSON');
        }
    }

    /**
     * @return array
     */
    public function provideInvalidJson()
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
