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
        return mb_strstr($type, ';', true) ?: $type;
    }
}
