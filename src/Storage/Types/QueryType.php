<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use Bolt\Storage\Parser\ContentFieldParser;
use Bolt\Storage\Resolver\QueryFieldResolver;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Ramsey\Uuid\Uuid;

class QueryType extends ObjectType
{
    private $contentFieldParser;

    public function __construct(
        ContentFieldParser $contentFieldParser,
        QueryFieldResolver $queryResolver,
        string $scope
    ) {
        $this->contentFieldParser = $contentFieldParser;

        $config = [
            'name' => 'Query',
            'fields' => $this->generateQueryFields(),
            'resolveField' => function ($val, array $args, $context, ResolveInfo $info) use ($queryResolver, $scope) {
                return $queryResolver->resolve($args, $info, $scope);
            },
        ];

        parent::__construct($config);
    }

    private function generateQueryFields(): array
    {
        $contentFields = $this->contentFieldParser->getParsedContentFields();
        $queryContentFields = [];
        foreach ($contentFields as $contentType => $fields) {
            $typeOfContent = Type::listOf(new ContentType($contentType, $fields));
            $queryContentFields[$contentType] = [
                'type' => $typeOfContent,
                'description' => 'Represents list of ' . $contentType,
                'args' => [
                    'first' => [
                        'type' => Type::int(),
                    ],
                    'latest' => [
                        'type' => Type::int(),
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'defaultValue' => 10,
                    ],
                    'random' => [
                        'type' => Type::int(),
                    ],
                    'order' => [
                        'type' => Type::getNullableType(new InputObjectType([
                            'name' => 'Order_' . Uuid::uuid4()->toString(),
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                                'direction' => [
                                    'type' => Type::string(),
                                ]
                            ],
                        ]))
                    ],
                    'filter' => [
                        'type' => Type::getNullableType(new InputObjectType([
                            'name' => 'ContentFilterInput_' . Uuid::uuid4()->toString(),
                            'fields' => $this->contentFieldParser->getContentFilterFields($contentType),
                        ])),
                    ],
                ],
            ];
        }

        return array_merge($queryContentFields, [
            'hello' => Type::listOf(Type::string()),
        ]);
    }
}
