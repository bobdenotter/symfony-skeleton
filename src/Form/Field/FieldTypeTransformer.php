<?php

declare(strict_types=1);

namespace Bolt\Form\Field;

use Bolt\Configuration\Content\FieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FieldTypeTransformer
{
    public static function fieldTypeToFormClass(FieldType $fieldDefinition): ?string
    {
        $namespace = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\';
        $typeClass = $namespace.ucfirst($fieldDefinition->get('type')).'Type';
        if (class_exists($typeClass)) {
            return $typeClass;
        }

        switch ($fieldDefinition->get('type')) {
            case 'select':
                return ChoiceType::class;
            // @todo add more explicit transformations from Bolt's type to Symfony's type
        }

        // if nothing found, let the Form Component guess the type
        return null;
    }
}
