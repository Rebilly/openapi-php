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
 * Class MethodsAllowedConstraintTest.
 */
class MethodsAllowedConstraintTest extends TestCase
{
    /**
     * @var MethodsAllowedConstraint
     */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->constraint = new MethodsAllowedConstraint(['OPTIONS', 'HEAD', 'GET']);
    }

    /**
     * @test
     * @dataProvider providerValidMethods
     *
     * @param string $method
     */
    public function assertValidMethod($method)
    {
        $this->assertThat($method, $this->constraint);
    }

    /**
     * @test
     */
    public function assertInvalidMethod()
    {
        try {
            $this->assertThat('POST', $this->constraint);
        } catch (Exception $e) {
            $this->assertContains($this->constraint->toString(), $e->getMessage());
        }

        if (!isset($e)) {
            $this->fail('Failed asserting the constraint');
        }
    }

    /**
     * @return array
     */
    public function providerValidMethods()
    {
        return [
            'Assert single method' => ['GET'],
            'Assert arrays are equals ' => [['OPTIONS', 'HEAD', 'GET']],
        ];
    }
}
