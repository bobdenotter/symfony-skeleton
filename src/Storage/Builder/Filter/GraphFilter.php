<?php

declare(strict_types=1);

namespace Bolt\Storage\Builder\Filter;

class GraphFilter
{
    private $field;

    private $searchValue;

    public function __construct(string $field, $searchValue)
    {
        $this->field = $field;
        $this->searchValue = $searchValue;
    }

    public static function createSimpleFilter(string $field, $value): self
    {
        return new self($field, $value);
    }

    public static function createOrFilter(...$graphFilters): self
    {
        return new self('OR', $graphFilters);
    }

    public static function createAndFilter(...$graphFilters): self
    {
        return new self('AND', $graphFilters);
    }

    public function __toString(): string
    {
        switch ($this->field) {
            case 'AND':
            case 'OR':
                return sprintf('{%s:[%s]}', $this->field, implode(',', $this->searchValue));
                break;
            default:
                $value = $this->getPreparedValue($this->searchValue);
                return sprintf('{%s:%s}', $this->field, $value);
                break;
        }
    }

    private function getPreparedValue($value): string
    {
        return is_numeric($value) ? "${value}" : '"'.$value.'"';
    }
}
