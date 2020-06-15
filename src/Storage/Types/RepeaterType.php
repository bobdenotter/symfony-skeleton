<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\ObjectType;
use Ramsey\Uuid\Uuid;

class RepeaterType extends ObjectType
{
    public function __construct(array $fields)
    {
        $config = [
            'name' => 'Repeater_'.Uuid::uuid4()->toString(),
            'fields' => $fields,
        ];
        parent::__construct($config);
    }
}
