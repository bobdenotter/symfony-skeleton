<?php

namespace Bolt\Storage\Exception;

use Exception;

class UnsupportedQueryException extends Exception
{
    public function __construct(string $textQuery)
    {
        $message = 'Unsupported query "%s"';
        parent::__construct(sprintf($message, $textQuery));
    }
}