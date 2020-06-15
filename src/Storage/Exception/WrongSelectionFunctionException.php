<?php

namespace Bolt\Storage\Exception;

use Exception;

class WrongSelectionFunctionException extends Exception
{
    public function __construct(string $functionName)
    {
        $message = sprintf('"%s" is not supported selection function.', $functionName);
        parent::__construct($message);
    }
}
