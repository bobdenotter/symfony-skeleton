<?php

declare(strict_types=1);

namespace Bolt\Storage\Handler;

use Bolt\Entity\Content;
use Bolt\Storage\ContentQueryParser;
use Bolt\Storage\Directive\LimitDirective;
use Bolt\Storage\Directive\PageDirective;
use Bolt\Storage\SelectQuery;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;

/**
 *  Handler class to perform select query and return a resultset.
 */
class SelectQueryHandler
{
    /**
     * @return Content|Pagerfanta|null
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $repo = $contentQuery->getContentRepository();
        $qb = $repo->getQueryBuilder();

        /** @var SelectQuery $selectQuery */
        $selectQuery = $contentQuery->getService('select');

        // Note: This might seem superfluous, but if we "re-use" $contentQuery,
        // this needs resetting.
        $selectQuery->setSingleFetchMode(null);

        $selectQuery->setQueryBuilder($qb);
        $selectQuery->setContentTypeFilter($contentQuery->getContentTypes());
        $selectQuery->setParameters($contentQuery->getParameters());

        $contentQuery->runScopes($selectQuery);

        // This is required. Not entirely sure why.
        $selectQuery->build();

        // Bolt4 introduces an extra table for field values, so additional
        // joins are required.
        $selectQuery->doReferenceJoins();
        $selectQuery->doTaxonomyJoins();
        $selectQuery->doFieldJoins();

        $contentQuery->runDirectives($selectQuery);

        if ($selectQuery->shouldReturnSingle()) {
            return $qb
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $query = $qb->getQuery();

        $amountPerPage = (int) $contentQuery->getDirective(LimitDirective::NAME);
        $page = $contentQuery->getDirective(PageDirective::NAME);
        if ($page !== null) {
            $page = (int) $page;
        }

        $request = $contentQuery->getRequest();

        return $this->createPaginator($request, $query, $amountPerPage, $page);
    }

    private function createPaginator(?Request $request, Query $query, int $amountPerPage, ?int $page = null): Pagerfanta
    {
//        dd($query->getDQL());

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false, true));
        $paginator->setMaxPerPage($amountPerPage);

        // If current page was not set explicitly.
        if ($page === null) {
            // If we don't have $request, we're likely not in a web context.
            if ($request) {
                $page = (int) $request->get('page', 1);
            } else {
                $page = 1;
            }
        }

        // If we have multiple pagers on page, we shouldn't allow one of the
        // pagers to go over the maximum, thereby throwing an exception. In this
        // case, this specific pager show stay on the last page.
        $paginator->setCurrentPage(min($page, $paginator->getNbPages()));

        return $paginator;
    }
}
