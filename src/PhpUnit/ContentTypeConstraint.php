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

namespace Rebilly\OpenAPI\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use function array_map;
use function implode;
use function in_array;
use function mb_strstr;

/**
 * Constraint that asserts that the content-type matches the expected types.
 *
 * TODO: Checking params, instead of skipping
 */
final class ContentTypeConstraint extends Constraint
{
    private $allowedTypes;

    public function __construct(array $allowedTypes)
    {
        $this->allowedTypes = array_map([$this, 'stripParams'], $allowedTypes);
    }

    public function toString(): string
    {
        return 'is an allowed content-type (' . implode(', ', $this->allowedTypes) . ')';
    }

    protected function matches($other): bool
    {
        return in_array($this->stripParams($other), $this->allowedTypes, true);
    }

    private static function stripParams(string $type): string
    {
        return mb_strstr($type, ';', true) ?: $type;
    }
}
