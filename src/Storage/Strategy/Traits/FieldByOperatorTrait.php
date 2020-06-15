<?php

namespace Bolt\Storage\Strategy\Traits;

use Bolt\Storage\Builder\FilterFieldBuilder;

trait FieldByOperatorTrait
{
    protected function getFieldByOperator(string $operator, string $field): string
    {
        switch ($operator) {
            case '%':
                return FilterFieldBuilder::contains($field);
            case '>':
                return FilterFieldBuilder::greaterThan($field);
            case '<':
                return FilterFieldBuilder::lessThan($field);
            case '>=':
                return FilterFieldBuilder::greaterThanEqual($field);
            case '<=':
                return FilterFieldBuilder::lessThanEqual($field);
            case '=':
            default:
                return $field;
                break;
        }
    }
}
