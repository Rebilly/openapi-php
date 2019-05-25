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

class MethodsAllowedConstraintTest extends TestCase
{
    /** @var MethodsAllowedConstraint */
    private $constraint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraint = new MethodsAllowedConstraint(['OPTIONS', 'HEAD', 'GET']);
    }

    /**
     * @test
     * @dataProvider providerValidMethods
     *
     * @param mixed $method
     */
    public function assertValidMethod($method): void
    {
        $this->assertThat($method, $this->constraint);
    }

    /**
     * @test
     */
    public function assertInvalidMethod(): void
    {
        try {
            $this->assertThat('POST', $this->constraint);
        } catch (ExpectationFailedException $e) {
            $this->assertContains($this->constraint->toString(), $e->getMessage());
        }

        if (!isset($e)) {
            $this->fail('Failed asserting the constraint');
        }
    }

    public function providerValidMethods(): array
    {
        return [
            'Assert single method' => ['GET'],
            'Assert arrays are equals ' => [['OPTIONS', 'HEAD', 'GET']],
        ];
    }
}
