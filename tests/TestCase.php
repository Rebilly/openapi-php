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

use PHPUnit\Framework;
use stdClass;
use UnexpectedValueException;

abstract class TestCase extends Framework\TestCase
{
    protected function getSchemaSource(): string
    {
        return __DIR__ . '/Doubles/openapi3.json';
    }

    protected function getSchemaFactory(): SchemaFactory
    {
        return new SchemaFactory();
    }

    final protected function createObject(array $array): stdClass
    {
        return json_decode(json_encode($array));
    }

    final protected function getDataSetName(): string
    {
        if (preg_match('/with data set "(.+)"/i', $this->getName(), $matches) === false) {
            throw new UnexpectedValueException('Data set name not found');
        }

        return $matches[1];
    }
}
