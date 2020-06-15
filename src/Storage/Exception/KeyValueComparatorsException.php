<?php

namespace Bolt\Storage\Exception;

use Exception;

class KeyValueComparatorsException extends Exception
{
    public function __construct()
    {
        $message = 'Where statement of setcontent should have exact the same comparators.';
        parent::__construct($message);
    }
}