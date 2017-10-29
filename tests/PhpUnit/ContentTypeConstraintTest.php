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

use PHPUnit\Framework\ExpectationFailedException;
use Rebilly\OpenAPI\TestCase;

/**
 * Class ContentTypeConstraintTest.
 */
class ContentTypeConstraintTest extends TestCase
{
    /**
     * @var ContentTypeConstraint
     */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
     *
     * @param string $type
     */
    public function assertValidType($type)
    {
        $this->assertThat($type, $this->constraint);
    }

    /**
     * @test
     */
    public function assertInvalidType()
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

    /**
     * @return array
     */
    public function providerValidTypes()
    {
        return [
            ['application/json'],
            ['application/json;charset=utf=8'],
            ['text/html'],
            ['text/html;charset=utf=8'],
        ];
    }
}
