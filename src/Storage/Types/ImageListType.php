<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Ramsey\Uuid\Uuid;

class ImageListType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'ImageList_'.Uuid::uuid4()->toString(),
            'fields' => [
                'filename' => Type::string(),
                'alt' => Type::string(),
                'path' => Type::string(),
                'media' => Type::string(),
                'thumbnail' => Type::string(),
                'fieldname' => Type::string(),
                'url' => Type::string(),
            ],
        ];

        parent::__construct($config);
    }
}
