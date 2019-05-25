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

use PHPUnit\Framework\Constraint\Constraint;

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
        parent::__construct();
        $this->allowedTypes = array_map([$this, 'stripParams'], $allowedTypes);
    }

    protected function matches($other): bool
    {
        return in_array($this->stripParams($other), $this->allowedTypes, true);
    }

    public function toString(): string
    {
        return 'is an allowed content-type (' . implode(', ', $this->allowedTypes) . ')';
    }

    private static function stripParams(string $type): string
    {
        return strstr($type, ';', true) ?: $type;
    }
}
