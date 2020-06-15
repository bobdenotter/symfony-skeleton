<?php

declare(strict_types=1);

namespace Bolt\Storage\Conditional;

class Types
{
    public const CONTAINS = '~contains';
    public const NOT_CONTAINS = '~not_contains';
    public const IN = '~in';
    public const NOT_IN = '~not_in';
    public const NOT = '~not';
    public const LESS_THAN = '~lt';
    public const LESS_THAN_EQUAL = '~lte';
    public const GREATER_THAN = '~gt';
    public const GREATER_THAN_EQUAL = '~gte';
}
