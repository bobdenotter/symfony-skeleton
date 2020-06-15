<?php

namespace Bolt\Storage\Strategy;

use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Builder\Filter\GraphFilter;
use Bolt\Storage\Strategy\Traits\SingleValueParserTrait;

class SingleValueSelectorArgumentStrategy implements ContentArgumentStrategyInterface
{
    use SingleValueParserTrait;

    private const PATTERN = '/\|{2}|\&{2}/';

    public function extendsByArguments(ContentBuilder $content, string $field, string $value): void
    {
        [$operator, $value] = $this->parseValue($value);

        $content->addFilter(GraphFilter::createSimpleFilter($this->getFieldByOperator($operator, $value), $value));
    }

    public function shouldBeCalled(string $field, string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 0;
    }
}
