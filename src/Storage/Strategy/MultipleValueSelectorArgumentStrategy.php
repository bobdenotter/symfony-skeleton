<?php

namespace Bolt\Storage\Strategy;

use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Strategy\Traits\MultipleKeyValueParserTrait;

class MultipleValueSelectorArgumentStrategy implements ContentArgumentStrategyInterface
{
    use MultipleKeyValueParserTrait;

    private const PATTERN = '/\|{2}|\&{2}/';

    public function extendsByArguments(ContentBuilder $content, string $field, string $value): void
    {
        [$operators, $values, $comparator] = $this->parseMultipleValue($value);
        $this->addFiltersForContent($content, $operators, $values, $comparator, $field);
    }

    public function shouldBeCalled(string $field, string $value): bool
    {
        return preg_match(self::PATTERN, $value);
    }
}
