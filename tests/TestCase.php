<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI;

use Exception;
use PHPUnit_Framework_Constraint_Exception as ExceptionConstraint;
use PHPUnit_Framework_Constraint_ExceptionCode as ExceptionCodeConstraint;
use PHPUnit_Framework_Constraint_ExceptionMessage as ExceptionMessageConstraint;
use PHPUnit_Framework_TestCase as BaseTestCase;
use UnexpectedValueException;

/**
 * Bases for test case.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @return string
     */
    protected function getSchemaSource()
    {
        return __DIR__ . '/Doubles/schema.json';
    }

    /**
     * @return SchemaFactory
     */
    protected function getSchemaFactory()
    {
        return new SchemaFactory();
    }

    /**
     * @param array $array
     *
     * @return object
     */
    final protected function createObject(array $array)
    {
        return json_decode(json_encode($array));
    }

    /**
     * @return string
     */
    final protected function getDataSetName()
    {
        if (preg_match('/with data set "(.+)"/i', $this->getName(), $matches) === false) {
            throw new UnexpectedValueException('Data set name not found');
        }

        return $matches[1];
    }

    /**
     * @param Exception $expected
     * @param Exception $actual
     */
    final protected function assertException(Exception $expected, Exception $actual)
    {
        $this->assertThat($actual, new ExceptionConstraint(get_class($expected)));
        $this->assertThat($actual, new ExceptionCodeConstraint($expected->getCode()));
        $this->assertThat($actual, new ExceptionMessageConstraint($expected->getMessage()));
    }
}