<?php

declare(strict_types=1);

namespace Bolt\Storage\Directive;

use Bolt\Entity\Field;
use Bolt\Storage\FieldQueryUtils;
use Bolt\Storage\QueryInterface;
use Bolt\Twig\Notifications;
use Bolt\Utils\ContentHelper;
use Bolt\Utils\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

/**
 *  Directive to alter query based on 'order' parameter.
 *
 *  eg: 'pages', ['order'=>'-publishedAt']
 */
class OrderDirective
{
    public const NAME = 'order';

    /** @var LocaleHelper */
    private $localeHelper;

    /** @var Environment */
    private $twig;

    /** @var Notifications */
    private $notifications;

    /** @var FieldQueryUtils */
    private $utils;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(LocaleHelper $localeHelper, Environment $twig, Notifications $notifications, FieldQueryUtils $utils, EntityManagerInterface $em)
    {
        $this->localeHelper = $localeHelper;
        $this->twig = $twig;
        $this->notifications = $notifications;
        $this->utils = $utils;
        $this->em = $em;
    }

    public function __invoke(QueryInterface $query, string $order): void
    {
        if ($order === '') {
            return;
        }

        $locale = $this->localeHelper->getCurrentLocale($this->twig)->get('code');

        // remove default order
        $query->getQueryBuilder()->resetDQLPart('orderBy');

        $separatedOrders = $this->getOrderBys($order);

        foreach ($separatedOrders as $order) {
            [ $order, $direction ] = $this->createSortBy($order);

            if ($order === 'title' && $this->getTitleFormat($query) !== null) {
                $order = ContentHelper::getFieldNames($this->getTitleFormat($query));
            }

            if (is_array($order)) {
                foreach ($order as $orderitem) {
                    $this->setOrderBy($query, $orderitem, $direction, $locale);
                }
            } else {
                $this->setOrderBy($query, $order, $direction, $locale);
            }
        }
    }

    /**
     * Set the query OrderBy directives
     * given an order (e.g. 'heading', 'id') and direction (ASC|DESC)
     */
    private function setOrderBy(QueryInterface $query, string $order, string $direction, string $locale): void
    {
        if (in_array($order, $query->getCoreFields(), true)) {
            $query->getQueryBuilder()->addOrderBy('content.' . $order, $direction);
        } elseif ($order === 'author') {
            $query
                ->getQueryBuilder()
                ->leftJoin('content.author', 'user')
                ->addOrderBy('user.username', $direction);
        } elseif (in_array($order, $query->getTaxonomyFields(), true)) {
            $taxonomy = 'taxonomy_' . $query->getIndex();
            $taxonomySlug = 'taxonomy_slug_' . $query->getIndex();
            $query
                ->getQueryBuilder()
                ->leftJoin('content.taxonomies', $taxonomy)
                ->andWhere($taxonomy . '.type = :' . $taxonomySlug)
                ->setParameter($taxonomySlug, $order)
                ->addOrderBy($taxonomy . '.name', $direction);
        } elseif ($this->isActualField($query, $order)) {


            $expression = $this->em->createQueryBuilder()
                    ->select('field')
                    ->from(Field::class, 'field')
                    ->leftJoin('field.content', 'field_content')
                    ->where('field.name = :field');

            $expression = 'COALESCE((' . $expression->__toString() . '), 500)';

            $query->getQueryBuilder()->setParameter('field', 'price');

            // Note the `lower()` in the `addOrderBy()`. It is essential to sorting the
            // results correctly. See also https://github.com/bolt/core/issues/1190
            // again: lower breaks postgresql jsonb compatibility, first cast as txt
            // cast as TEXT or CHAR, depending on SQL support. See Bolt\Doctrine\Query\Cast.php
            // and https://github.com/bolt/core/issues/2241
            dump($expression);
            $query
                ->getQueryBuilder()
                ->addOrderBy('cast('.$expression.' as text)', $direction);

//            dd('lower(CAST(' . $expression . ' as TEXT))');

//            $query->getQueryBuilder()
//                ->addOrderBy($expression);

            return;

            $fieldsAlias = 'fields_order_' . $query->getIndex();
            $fieldAlias = 'order_' . $query->getIndex();
            $translationsAlias = 'translations_order_' . $query->getIndex();

            $query
                ->getQueryBuilder()
                ->leftJoin('content.fields', $fieldsAlias)
                ->leftJoin($fieldsAlias . '.translations', $translationsAlias)
                ->andWhere($fieldsAlias . '.name = :' . $fieldAlias)
                ->setParameter($fieldAlias, $order);

            if ($this->utils->isLocalizedField($query, $order)) {
                // If the field is localized, we limit the query to the
                // value for the current locale only.
                $query
                    ->getQueryBuilder()
                    ->andWhere($translationsAlias . '.locale = :' . $fieldAlias . '_locale')
                    ->setParameter($fieldAlias . '_locale', $locale);
            }

            if ($this->utils->isNumericField($query, $order)) {
                $this->orderByNumericField($query, $translationsAlias, $direction);
            } else {
                // Note the `lower()` in the `addOrderBy()`. It is essential to sorting the
                // results correctly. See also https://github.com/bolt/core/issues/1190
                // again: lower breaks postgresql jsonb compatibility, first cast as txt
                // cast as TEXT or CHAR, depending on SQL support. See Bolt\Doctrine\Query\Cast.php
                // and https://github.com/bolt/core/issues/2241
                $query
                    ->getQueryBuilder()
                    ->addOrderBy('lower(CAST(' . $translationsAlias . '.value as TEXT))', $direction);
            }
            $query->incrementIndex();
        } else {
            $this->notifications->warning('Incorrect OrderBy clause for field that does not exist',
                "A query with ordering on a Field or Taxonomy (`${order}`) that's not defined, will yield unexpected results. Update your `{% setcontent %}`-statement");
        }
    }

    /**
     * Cobble together the sorting order, and whether or not it's a column in `content` or `fields`.
     */
    private function createSortBy(string $order): array
    {
        if (mb_strpos($order, '-') === 0) {
            $direction = 'DESC';
            $order = mb_substr($order, 1);
        } elseif (mb_strpos($order, ' DESC') !== false) {
            $direction = 'DESC';
            $order = str_replace(' DESC', '', $order);
        } else {
            $order = str_replace(' ASC', '', $order);
            $direction = 'ASC';
        }

        return [$order, $direction];
    }

    protected function getOrderBys(string $order): array
    {
        $separatedOrders = [$order];

        if ($this->isMultiOrderQuery($order)) {
            $separatedOrders = explode(',', $order);
        }

        return $separatedOrders;
    }

    protected function isMultiOrderQuery(string $order): bool
    {
        return mb_strpos($order, ',') !== false;
    }

    protected function isActualField(QueryInterface $query, string $name): bool
    {
        $contentType = $query->getConfig()->get('contenttypes/' . $query->getContentType());

        return in_array($name, $contentType->get('fields')->keys()->all(), true);
    }

    private function getTitleFormat(QueryInterface $query): ?string
    {
        $contentType = $query->getConfig()->get('contenttypes/' . $query->getContentType());

        return $contentType->get('title_format', null);
    }

    private function orderByNumericField(QueryInterface $query, string $translationsAlias, string $direction): void
    {
        $qb = $query->getQueryBuilder();

        if (! $this->utils->hasCast()) {
            $qb->addOrderBy($translationsAlias . '.value', $direction);

            return;
        }

        $expression = $this->utils->getNumericCastExpression($translationsAlias . '.value');
        $qb->addOrderBy($expression, $direction);
    }
}
