<?php

namespace Bolt\Storage\Strategy;

use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Exception\WrongSelectorArgumentException;

class StatementSelectorArgumentStrategy implements ContentArgumentStrategyInterface
{
    public const SELECTORS = ['limit', 'order'];

    public function extendsByArguments(ContentBuilder $content, string $field, string $value): void
    {
        if (in_array($field, self::SELECTORS) === false) {
            throw new WrongSelectorArgumentException($field);
        }

        if ($field === 'limit') {
            $content->setLimit((int) $value);
            return;
        }

        if ($field === 'order' && mb_strpos($value, ',') !== false) {
            array_map(function($element) use ($content) {
                $direction = 'ASC';
                if ($element[0] === '-') {
                    $direction = 'DESC';
                    $element = substr($element, 1);
                }
                $content->addOrder(trim($element), $direction);
            }, explode(',', $value));

            return;
        }

        $direction = 'ASC';
        if ($value[0] === '-') {
            $direction = 'DESC';
            $value = substr($value, 1);
        }
        $content->setOrder($value, $direction);
    }

    public function shouldBeCalled(string $field, string $value): bool
    {
        return in_array($field, self::SELECTORS);
    }
}
