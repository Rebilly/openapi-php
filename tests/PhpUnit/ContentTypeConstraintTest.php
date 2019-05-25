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

namespace Rebilly\OpenAPI\PhpUnit;

use PHPUnit\Framework\ExpectationFailedException;
use Rebilly\OpenAPI\TestCase;

class ContentTypeConstraintTest extends TestCase
{
    /** @var ContentTypeConstraint */
    private $constraint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraint = new ContentTypeConstraint(
            [
                'application/json',
                'text/html;charset=utf-8',
            ]
        );
    }

    /**
     * @test
     * @dataProvider providerValidTypes
     */
    public function assertValidType(string $type): void
    {
        $this->assertThat($type, $this->constraint);
    }

    /**
     * @test
     */
    public function assertInvalidType(): void
    {
        try {
            $this->assertThat('text/json', $this->constraint);
        } catch (ExpectationFailedException $e) {
            $this->assertContains($this->constraint->toString(), $e->getMessage());
        }

        if (!isset($e)) {
            $this->fail('Failed asserting the constraint');
        }
    }

    public function providerValidTypes(): array
    {
        return [
            ['application/json'],
            ['application/json;charset=utf=8'],
            ['text/html'],
            ['text/html;charset=utf=8'],
        ];
    }
}
