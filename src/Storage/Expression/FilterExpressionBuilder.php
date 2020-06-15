<?php

declare(strict_types=1);

namespace Bolt\Storage\Expression;

use Bolt\Storage\Conditional\Types;
use Doctrine\ORM\Query\Expr;
use Ramsey\Uuid\Uuid;

class FilterExpressionBuilder
{
    private const ORX = 'OR';

    private const ANDX = 'AND';

    private $nestedFilterParams = [
        'OR', 'AND',
    ];

    public function build(
        string $filterName,
        $filterOptions,
        array &$parameters,
        string $alias,
        string $translatableAlias
    ): Expr\Andx {
        $expressions = [];
        if (in_array($filterName, $this->nestedFilterParams, true)) {
            $this->generateNestedExpr(
                $expressions,
                $filterName,
                $filterOptions,
                $parameters,
                $alias,
                $translatableAlias
            );
        } else {
            $expressions[$filterName] = $this->getExpressionForField(
                $filterName,
                $filterOptions,
                $parameters,
                $alias,
                $translatableAlias
            );
        }

        return (new Expr())->andX(...array_values($expressions));
    }

    private function generateNestedExpr(
        array &$expressions,
        string $filterName,
        $filterOptions,
        array &$parameters,
        string $alias,
        string $translatableAlias
    ): void {
        $expr = new Expr();
        switch ($filterName) {
            case self::ORX:
                $orExpressions = [];
                foreach ($filterOptions as $filterKeyValue) {
                    $filterField = key($filterKeyValue);
                    $filterValue = $filterKeyValue[$filterField];
                    if (in_array($filterField, $this->nestedFilterParams, true)) {
                        $this->generateNestedExpr(
                            $orExpressions,
                            $filterName,
                            reset($filterOptions),
                            $parameters,
                            $alias,
                            $translatableAlias
                        );
                    } else {
                        $orExpressions[] = $this->getExpressionForField(
                            $filterField,
                            $filterValue,
                            $parameters,
                            $alias,
                            $translatableAlias
                        );
                    }
                }

                $expressions[] = $expr->orX(...array_values($orExpressions));
                break;
            case self::ANDX:
                $andExpressions = [];
                foreach ($filterOptions as $filterKeyValue) {
                    $filterField = key($filterKeyValue);
                    $filterValue = $filterKeyValue[$filterField];
                    if (in_array($filterField, $this->nestedFilterParams, true)) {
                        $this->generateNestedExpr(
                            $andExpressions,
                            $filterName,
                            $filterOptions,
                            $parameters,
                            $alias,
                            $translatableAlias
                        );
                    } else {
                        $andExpressions[] = $this->getExpressionForField(
                            $filterField,
                            $filterValue,
                            $parameters,
                            $alias,
                            $translatableAlias
                        );
                    }
                }
                $expressions[] = $expr->andX(...array_values($andExpressions));
                break;
        }
    }

    private function getExpressionForField(
        string $fieldName,
        $fieldValue,
        array &$parameters,
        string $alias,
        string $translatableAlias
    ): Expr\Andx
    {
        $expr = new Expr();
        $andFieldExpressions = [];

        [$field, $operation] = $this->getFieldOperation($fieldName);
        $parameterName = $this->getUniqueParameterName($field);

        $parameters[$parameterName] = $fieldValue;

        $andFieldExpressions[] = $expr->eq($alias.'.name', ':fieldName'.ucfirst($field));
        $parameters['fieldName'.ucfirst($field)] = $field;

        switch ($operation) {
            case Types::CONTAINS:
                $parameters[$parameterName] = '%'.$fieldValue.'%';
                $andFieldExpressions[] = $expr->like($translatableAlias.'.value', $parameterName);
                break;
            case Types::NOT_CONTAINS:
                $parameters[$parameterName] = '%'.$fieldValue.'%';
                $andFieldExpressions[] = $expr->notLike($translatableAlias.'.value', $parameterName);
                break;
            case Types::NOT:
                $andFieldExpressions[] = $expr->neq($translatableAlias.'.value', $parameterName);
                break;
            case Types::NOT_IN:
                $andFieldExpressions[] = $expr->notIn($translatableAlias.'.value', $parameterName);
                break;
            case Types::IN:
                $andFieldExpressions[] = $expr->in($translatableAlias.'.value', $parameterName);
                break;
            case Types::GREATER_THAN:
                $andFieldExpressions[] = $expr->gt($translatableAlias.'.value', $parameterName);
                break;
            case Types::GREATER_THAN_EQUAL:
                $andFieldExpressions[] = $expr->gte($translatableAlias.'.value', $parameterName);
                break;
            case Types::LESS_THAN:
                $andFieldExpressions[] = $expr->lt($translatableAlias.'.value', $parameterName);
                break;
            case Types::LESS_THAN_EQUAL:
                $andFieldExpressions[] = $expr->lte($translatableAlias.'.value', $parameterName);
                break;
            default:
                $andFieldExpressions[] = $expr->eq($translatableAlias.'.value', $parameterName);
                break;
        }

        return $expr->andX(...$andFieldExpressions);
    }

    private function getFieldOperation(string $fieldName): array
    {
        $exploded = explode('~', $fieldName);

        if (count($exploded) === 2) {
            return [$exploded[0], '~'.$exploded[1]];
        }

        return [$fieldName, null];
    }

    private function getUniqueParameterName(string $fieldName): string
    {
        $parameterName = ':'.$fieldName;
        $uniqueValue = mb_substr(Uuid::uuid4()->toString(), 0, 5);

        return $parameterName.'_'.$uniqueValue;
    }
}
