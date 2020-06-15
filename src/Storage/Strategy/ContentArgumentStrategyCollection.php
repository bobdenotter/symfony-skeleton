<?php

namespace Bolt\Storage\Strategy;

use Tightenco\Collect\Support\Collection;

class ContentArgumentStrategyCollection extends Collection
{
    public function selectStrategy(string $field, string $value): ?ContentArgumentStrategyInterface
    {
        /**
         * @var ContentArgumentStrategyInterface $strategy
         */
        foreach ($this->items as $strategy) {
            if ($strategy->shouldBeCalled($field, $value)) {
                return $strategy;
            }
        }

        return null;
    }
}
