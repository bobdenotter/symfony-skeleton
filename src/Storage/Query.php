<?php

declare(strict_types=1);

namespace Bolt\Storage;

use Bolt\Storage\Exception\QueryErrorException;
use Bolt\Storage\Parser\ContentFieldParser;
use Bolt\Storage\Parser\QueryParser;
use Bolt\Storage\Resolver\QueryFieldResolver;
use Bolt\Storage\Scope\ScopeEnum;
use Bolt\Storage\Types\QueryType;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Symfony\Component\HttpFoundation\JsonResponse;

class Query
{
    private $contentFieldParser;

    private $queryFieldResolver;

    private $queryParser;

    public function __construct(
        ContentFieldParser $contentFieldParser,
        QueryFieldResolver $queryFieldResolver,
        QueryParser $queryParser
    ) {
        $this->contentFieldParser = $contentFieldParser;
        $this->queryFieldResolver = $queryFieldResolver;
        $this->queryParser = $queryParser;
    }

    public function getContent(string $textQuery, array $whereArguments = []): JsonResponse
    {
        $schema = new Schema([
            'query' => new QueryType(
                $this->contentFieldParser,
                $this->queryFieldResolver,
                ScopeEnum::DEFAULT
            ),
        ]);
        $result = GraphQL::executeQuery($schema, $this->queryParser->parseQuery($textQuery, $whereArguments));

        return new JsonResponse($result->toArray());
    }

    public function getContentForTwig(string $textQuery, array $arguments = []): array
    {
        $schema = new Schema([
            'query' => new QueryType(
                $this->contentFieldParser,
                $this->queryFieldResolver,
                ScopeEnum::FRONT
            ),
        ]);

        $returnSingle = isset($arguments['returnsingle']);
        unset($arguments['returnsingle']);

        $textQuery = $this->queryParser->parseQuery($textQuery, $arguments);
        $result = GraphQL::executeQuery($schema, $textQuery);

        if (empty($result->errors) === false) {
            throw new QueryErrorException($result->errors);
        }

        $content = reset($result->toArray()['data']);

        if (empty($content)) {
            return [];
        }

        if ($returnSingle || (count($content) === 1 && isset($arguments['limit']) === false)) {
            return reset($content);
        }

        return $content;
    }
}
