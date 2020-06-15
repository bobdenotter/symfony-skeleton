<?php

namespace Bolt\Storage\Exception;

use Exception;

class WrongConditionConnectionException extends Exception
{
    public function __construct()
    {
        parent::__construct('You cannot use first and last function together.');
    }
}
