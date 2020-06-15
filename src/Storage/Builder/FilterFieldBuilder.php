<?php

declare(strict_types=1);

namespace Bolt\Storage\Builder;

use Bolt\Storage\Conditional\Types;

class FilterFieldBuilder
{
    public static function contains(string $fieldName): string
    {
        return $fieldName . Types::CONTAINS;
    }

    public static function notContains(string $fieldName): string
    {
        return $fieldName . Types::NOT_CONTAINS;
    }

    public static function in(string $fieldName): string
    {
        return $fieldName . Types::IN;
    }

    public static function notIn(string $fieldName): string
    {
        return $fieldName . Types::NOT_IN;
    }

    public static function not(string $fieldName): string
    {
        return $fieldName . Types::NOT;
    }

    public static function lessThan(string $fieldName): string
    {
        return $fieldName . Types::LESS_THAN;
    }

    public static function lessThanEqual(string $fieldName): string
    {
        return $fieldName . Types::LESS_THAN_EQUAL;
    }

    public static function greaterThan(string $fieldName): string
    {
        return $fieldName . Types::GREATER_THAN;
    }

    public static function greaterThanEqual(string $fieldName): string
    {
        return $fieldName . Types::GREATER_THAN_EQUAL;
    }
}
