<?php

declare(strict_types=1);

namespace Bolt\Storage\Types;

use GraphQL\Type\Definition\StringType;
use Ramsey\Uuid\Uuid;

class DateType extends StringType
{
    public function __construct(array $config = [])
    {
        $this->name = 'Date_' . Uuid::uuid4()->toString();
        parent::__construct($config);
    }
}
