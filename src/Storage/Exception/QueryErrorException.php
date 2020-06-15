<?php

namespace Bolt\Storage\Exception;

use Exception;
use GraphQL\Error\Error;

class QueryErrorException extends Exception
{
    /**
     * @param array<Error> $errors
     */
    public function __construct(array $errors)
    {
        $html = 'Query Error Exception Occured. Errors list:<ul>';
        foreach ($errors as $error) {
            $html .= '<li>'.$error->message.'</li>';
        }
        $html .= '</ul>';
        parent::__construct($html);
    }
}