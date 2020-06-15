<?php

namespace Bolt\Storage\Strategy;

use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Strategy\Traits\MultipleKeyValueParserTrait;

class MultipleKeyValueSelectorArgumentStrategy implements ContentArgumentStrategyInterface
{
    use MultipleKeyValueParserTrait;

    private const MULTIPLE_PATTERN = '/\|{3}|\&{3}/';

    public function extendsByArguments(ContentBuilder $content, string $field, string $value): void
    {
        [$operators, $keyValues, $comparator] = $this->parseMultipleKeyValue($field, $value);
        $this->addFiltersForContent($content, $operators, $keyValues, $comparator, $field, true);
    }

    public function shouldBeCalled(string $field, string $value): bool
    {
        return preg_match(self::MULTIPLE_PATTERN, $field) && preg_match(self::MULTIPLE_PATTERN, $value);
    }
}
