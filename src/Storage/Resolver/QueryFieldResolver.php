<?php

declare(strict_types=1);

namespace Bolt\Storage\Resolver;

use ArrayObject;
use Bolt\Entity\Content;
use Bolt\Entity\Field;
use Bolt\Storage\Criteria\ContentCriteria;
use Bolt\Storage\Criteria\PublishedCriteria;
use Bolt\Storage\Expression\FilterExpressionBuilder;
use Bolt\Storage\Scope\ScopeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use GraphQL\Type\Definition\ResolveInfo;

class QueryFieldResolver
{
    private $filterExpressionBuilder;
    private $entityManager;

    public function __construct(
        FilterExpressionBuilder $filterExpressionBuilder,
        EntityManagerInterface $entityManager
    ) {
        $this->filterExpressionBuilder = $filterExpressionBuilder;
        $this->entityManager = $entityManager;
    }

    public function resolve(array $args, ResolveInfo $info, string $scope): array
    {
        if ($info->fieldName === 'hello') {
            return $this->helloMessage();
        }

        return $this->contentResolve($args, $info, $scope);
    }

    private function helloMessage(): array
    {
        return [
            'This message will be shown if welcome query works!',
        ];
    }

    private function contentResolve(array $args, ResolveInfo $info, string $scope): array
    {
        $contentTypeAlias = 'c';

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select($contentTypeAlias)
            ->from(Field::class, 'bf1')
            ->innerJoin(
                Content::class,
                $contentTypeAlias,
                Join::WITH,
                sprintf('%s.id = %s.content', $contentTypeAlias, 'bf1')
            );

        if (isset($args['filter'])) {
            $expressions = $this->filterExpressionBuilder->build($args['filter']);
            $parameters = $this->filterExpressionBuilder->getParametersValues();
            $aliasCounter = $this->filterExpressionBuilder->getAliasCounter();

            for ($i = 2; $i <= $aliasCounter; $i++) {
                $alias = 'bf'.$i;
                $qb->innerJoin(
                    Field::class,
                    $alias,
                    Join::WITH,
                    sprintf('%s.id = %s.content', $contentTypeAlias, $alias)
                );
            }

            $qb->where($expressions)->setParameters($parameters);
        }

        if ($info->fieldName !== 'content') {
            $qb->addCriteria(
                (new ContentCriteria())->getCriteria($info->fieldName, $contentTypeAlias)
            );
        }

        if ($scope === ScopeEnum::FRONT) {
            $qb->addCriteria(
                (new PublishedCriteria())->getCriteria($contentTypeAlias)
            );
        }

        if (isset($args['random'])) {
            unset($args['limit']);
        }

        if (isset($args['first'])) {
            $args['limit'] = $args['first'];
            $qb->addOrderBy(sprintf('%s.createdAt', $contentTypeAlias), 'ASC');
        }

        if (isset($args['latest'])) {
            $args['limit'] = $args['latest'];
            if ($args['order']) {
                $qb->addOrderBy(
                    sprintf('%s.%s', $contentTypeAlias, $this->getFieldName($args['order']['field'])),
                    $args['order']['direction'] ?? 'ASC'
                );
            }
        }

        if (isset($args['order'])) {
            if (mb_strpos($args['order']['field'], ',') === false) {
                $qb->andWhere('bf1.name = :orderFieldName')
                    ->setParameter('orderFieldName', $args['order']['field']);
                $qb->addOrderBy('bf1.value', $args['order']['direction'] ?? 'ASC');
            } else {
                $fields = explode(',', $args['order']['field']);
                $directions = explode(',', $args['order']['direction']);
                foreach ($fields as $key => $field) {
                    $alias = 'bf'.substr(md5($key.time()), 0, 5);
                    $qb->innerJoin(
                        Field::class,
                        $alias,
                        Join::WITH,
                        sprintf('%s.id = %s.content', $contentTypeAlias, $alias)
                    );

                    $qb->andWhere(sprintf('%s.name = :orderFieldName%d', $alias, $key))
                        ->setParameter(sprintf('orderFieldName%d', $key), $field);
                    $qb->addOrderBy(sprintf('%s.value', $alias), $directions[$key]);
                }
            }
        }

        if (isset($args['limit'])) {
            $qb->setMaxResults($args['limit']);
        }
        $qb->groupBy(sprintf('%s.id', $contentTypeAlias));
        $results = $qb->getQuery()->execute();

        if (isset($args['random'])) {
            shuffle($results);
            $results = array_slice($results, 0, $args['random']);
        }

        return $this->getPreparedResults($results, $info->getFieldSelection());
    }

    private function getPreparedResults(array $results, array $fields): array
    {
        $preparedResults = [];
        $returnAllFields = in_array('*', array_keys($fields), true);
        /** @var Content $result */
        foreach ($results as $resultKey => $result) {
            $arrayResult = $result->jsonSerialize();
            if ($returnAllFields) {
                foreach (array_keys($arrayResult['fields']) as $contentField) {
                    $preparedResults[$resultKey][$contentField] = $arrayResult['fields'][$contentField];
                }
            } else {
                foreach (array_keys($fields) as $key) {
                    if (array_key_exists($key, $arrayResult['fields'])) {
                        $preparedResults[$resultKey][$key] = $arrayResult['fields'][$key];
                    }
                }
                foreach (array_keys($arrayResult) as $contentField) {
                    $preparedResults[$resultKey][$contentField] = $arrayResult[$contentField];
                }
                $preparedResults[$resultKey] = new ArrayObject($preparedResults[$resultKey]);
            }
        }

        return $preparedResults;
    }

    private function getFieldName(string $fieldName): string
    {
        return preg_replace_callback('/_([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $fieldName);
    }
}
