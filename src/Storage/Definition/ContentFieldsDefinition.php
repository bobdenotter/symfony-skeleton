<?php

declare(strict_types=1);

namespace Bolt\Storage\Definition;

use Bolt\Storage\Types\AuthorType;
use Bolt\Storage\Types\DateType;
use GraphQL\Type\Definition\Type;

class ContentFieldsDefinition
{
    public static function getMainContentFields(bool $typeAsArray = false): array
    {
        if ($typeAsArray) {
            $contentEntityTypes = [
                'id' => ['type' => 'id'],
                'contentType' => ['type' => 'string'],
                'author' => ['type' => 'user'],
                'status' => ['type' => 'string'],
                'icon' => ['type' => 'string'],
                'createdAt' => ['type' => 'string'],
                'modifiedAt' => ['type' => 'string'],
                'publishedAt' => ['type' => 'string'],
                'depublishedAt' => ['type' => 'string'],
            ];
        } else {
            $contentEntityTypes = [
                'id' => Type::id(),
                'contentType' => Type::string(),
                'author' => new AuthorType(),
                'status' => Type::string(),
                'icon' => Type::string(),
                'createdAt' => new DateType(),
                'modifiedAt' => new DateType(),
                'publishedAt' => new DateType(),
                'depublishedAt' => new DateType(),
            ];
        }
        $mainContentFields = [];
        foreach (array_keys($contentEntityTypes) as $fieldName) {
            $mainContentFields[$fieldName] = $contentEntityTypes[$fieldName];
        }

        return $mainContentFields;
    }
}
