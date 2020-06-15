<?php

declare(strict_types=1);

namespace Bolt\Storage\Criteria;

use Doctrine\Common\Collections\Criteria;

class ContentCriteria implements ParameterizedCriteriaInterface
{
    public function getCriteria(string $parameter, string $alias = 'c'): Criteria
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create();

        return $criteria
            ->where(
                $expr->eq(sprintf('%s.contentType', $alias), $parameter)
            );
    }
}
