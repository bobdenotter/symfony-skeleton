<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Ramsey\Uuid\Uuid;

class AuthorType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Author_'.Uuid::uuid4()->toString(),
            'fields' => [
                'id' => Type::id(),
                'displayName' => Type::string(),
                'username' => Type::string(),
                'email' => Type::string(),
            ],
        ];
        parent::__construct($config);
    }
}
