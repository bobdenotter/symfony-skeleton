<?php

declare(strict_types=1);

namespace Bolt\Storage\Helper;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

class Query
{
    public static function dump(QueryBuilder $queryBuilder): string
    {
        $query = $queryBuilder->getQuery();

        $sql = $query->getDQL();
        $parameters = $query->getParameters();

        $dumped = $sql;
        /** @var Parameter $parameter */
        foreach ($parameters as $parameter) {
            $dumped = preg_replace(
                '/\:'.$parameter->getName().'/',
                "'{$parameter->getValue()}'",
                $dumped,
                1
            );
        }

        $query->setDQL($dumped);

        return $query->getSQL();
    }
}
