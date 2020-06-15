<?php

declare(strict_types=1);

namespace Bolt\Doctrine\Mapping;

use Bolt\Common\Json;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Seld\JsonLint\JsonParser;

class FieldValueType extends Type
{
    private const TYPENAME = 'field_value';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (Json::test($value)) {
            $parsedValue = (new JsonParser())->parse($value, JsonParser::PARSE_TO_ASSOC);
            if (is_int($parsedValue)) {
                return (string) $parsedValue;
            }
            return $parsedValue;
        }

        return (string) $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return Json::dump($value);
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::TYPENAME;
    }
}
