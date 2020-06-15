<?php

namespace Bolt\Storage\Exception;

use Bolt\Storage\Strategy\StatementSelectorArgumentStrategy;
use Exception;

class WrongSelectorArgumentException extends Exception
{
    public function __construct(string $field)
    {
        $message = sprintf(
            '"%s" is not supported as selector argument. Only [%s] supported.',
            $field,
            implode(', ', StatementSelectorArgumentStrategy::SELECTORS)
        );

        parent::__construct($message);
    }
}
