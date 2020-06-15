<?php

namespace Bolt\Storage\Types;

use Bolt\Common\Json;
use Bolt\Entity\Field;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ObjectType;
use Ramsey\Uuid\Uuid;

class DataType extends ObjectType implements LeafType
{
    public function __construct()
    {
        $config = [
            'name' => 'Data_'.Uuid::uuid4()->toString(),
        ];
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function serialize($value)
    {
        $values = [];
        foreach ($value as $key => $val) {
            if ($val instanceof Field) {
                $values[] = $val->getValue();
            }
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function parseValue($value)
    {
        if (is_array($value)) {
            return Json::dump($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {

    }
}