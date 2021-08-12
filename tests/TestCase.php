<?php

declare(strict_types=1);
/**
 * This source file is proprietary and part of Rebilly.
 *
 * (c) Rebilly SRL
 *     Rebilly Ltd.
 *     Rebilly Inc.
 *
 * @see https://www.rebilly.com
 */

namespace Rebilly\OpenAPI;

use PHPUnit\Framework;
use stdClass;
use UnexpectedValueException;
use function json_decode;
use function json_encode;
use function preg_match;

abstract class TestCase extends Framework\TestCase
{
    protected function getSchemaSource(): string
    {
        return __DIR__ . '/Doubles/openapi_3.0.0.json';
    }

    protected function getSchema(): Schema
    {
        return new Schema($this->getSchemaSource());
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
