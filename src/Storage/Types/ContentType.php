<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ContentType extends ObjectType
{
    public function __construct(string $contentType, array $fields)
    {
        $config = [
            'name' => 'Content ' . $contentType,
            'fields' => function () use ($fields) {
                return array_merge($fields, [
                    '*' => Type::string(),
                ]);
            },
        ];
        parent::__construct($config);
    }
}
