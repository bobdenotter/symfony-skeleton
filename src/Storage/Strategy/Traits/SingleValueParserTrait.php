<?php

namespace Bolt\Storage\Strategy\Traits;

trait SingleValueParserTrait
{
    use FieldByOperatorTrait;

    protected $valuePattern = '/^([\<|\>\%]?=?)/';

    protected function parseValue(string $value): array
    {
        preg_match($this->valuePattern, $value, $matches);

        if (empty($matches[0])) {
            return [null, $value];
        }

        return [$matches[0], mb_substr($value, mb_strlen($matches[0]))];
    }
}