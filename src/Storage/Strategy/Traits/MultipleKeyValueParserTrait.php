<?php

namespace Bolt\Storage\Strategy\Traits;

use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Builder\Filter\GraphFilter;
use Bolt\Storage\Exception\KeyValueComparatorsException;

trait MultipleKeyValueParserTrait
{
    use FieldByOperatorTrait;

    protected $multipleValuePattern =  '/^([\<|\>\%]?=?)(.[^\s|&]*)\s*(\|{2}|\&{2})\s*([\<|\>\%]?=?)(.*)$/';
    protected $multipleKeyPattern = '/^(.[^\s|&]*)\s*(\|{3}|\&{3})\s*(.*)$/';

    protected function parseMultipleValue(string $value): array
    {
        preg_match($this->multipleValuePattern, $value, $matches);
        [, $operatorFieldOne, $valueOne, $valueComparator, $operatorFieldTwo, $valueTwo] = $matches;

        return [[$operatorFieldOne, $operatorFieldTwo], [$valueOne, $valueTwo], $valueComparator];
    }

    protected function parseMultipleKeyValue(string $key, string $value): array
    {
        preg_match($this->multipleKeyPattern, $key, $keyMatches);
        preg_match($this->multipleValuePattern, $value, $valueMatches);

        [, $fieldOne, $keyComparator, $fieldTwo] = $keyMatches;
        [, $operatorFieldOne, $valueOne, $valueComparator, $operatorFieldTwo, $valueTwo] = $valueMatches;

        if ($keyComparator !== $valueComparator) {
            throw new KeyValueComparatorsException();
        }

        return [
            [$operatorFieldOne, $operatorFieldTwo],
            [
                [$fieldOne => $valueOne],
                [$fieldTwo => $valueTwo],
            ],
            $keyComparator
        ];
    }

    protected function addFiltersForContent(
        ContentBuilder $content,
        array $operators,
        array $values,
        string $comparator,
        string $field,
        bool $getValueItself = false
    ): void {
        $fieldsForFilter = [];
        $vals = [];

        foreach ($values as $id => $val) {
            $value = $val;
            if ($getValueItself) {
                $field = key($val);
                $value = $val[$field];
            }
            $vals[] = $value;

            $fieldsForFilter[] = $this->getFieldByOperator($operators[$id], $field);
        }

        $values = $vals;

        switch ($comparator) {
            case '&&':
            case '&&&':
                $content->addFilter(GraphFilter::createAndFilter(
                    GraphFilter::createSimpleFilter($fieldsForFilter[0], $values[0]),
                    GraphFilter::createSimpleFilter($fieldsForFilter[1], $values[1])
                ));
                break;
            case '||':
            case '|||':
                $content->addFilter(GraphFilter::createOrFilter(
                    GraphFilter::createSimpleFilter($fieldsForFilter[0], $values[0]),
                    GraphFilter::createSimpleFilter($fieldsForFilter[1], $values[1])
                ));
                break;
        }
    }
}
