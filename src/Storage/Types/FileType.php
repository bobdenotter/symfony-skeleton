<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Ramsey\Uuid\Uuid;

class FileType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'File_'.Uuid::uuid4()->toString(),
            'fields' => [
                'filename' => Type::string(),
                'alt' => Type::string(),
                'media' => Type::string(),
            ],
        ];

        parent::__construct($config);
    }
}
