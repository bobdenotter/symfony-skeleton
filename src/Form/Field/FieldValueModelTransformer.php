<?php

declare(strict_types=1);

namespace Bolt\Form\Field;

use Bolt\Configuration\Content\FieldType;
use Bolt\Configuration\PathResolver;
use Bolt\Entity\Field;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

class FieldValueModelTransformer implements DataTransformerInterface
{
    /**
     * @var FieldType
     */
    private $fieldDefinition;

    /**
     * @var PathResolver
     */
    private $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function forField(FieldType $fieldDefinition): self
    {
        $new = clone $this;
        $new->setFieldDefinition($fieldDefinition);

        return $new;
    }

    public function setFieldDefinition(FieldType $fieldDefinition): void
    {
        $this->fieldDefinition = $fieldDefinition;
    }

    /**
     * array => parsed value
     */
    public function transform($value)
    {
        $value = Field::parseValue($value);
        return $this->furtherTransform($value);
    }

    private function furtherTransform($value)
    {
        switch ($this->fieldDefinition->getType()) {
            case 'checkbox':
                return (bool) $value;
            case 'file':
            case 'image':
                return new File(sprintf(
                    '%s/%s',
                    $this->pathResolver->resolve('files'),
                    $value['filename']
                ));
            case 'date':
                return \DateTime::createFromFormat('Y-m-d', $value) ?: null;
            case 'datetime':
                return \DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: null;
            case 'number':
                return (float) $value;
            case 'integerfield':
                return (int) $value;
            default:
                return $value;
        }
    }

    /**
     * parsed value => array
     */
    public function reverseTransform($value)
    {
        $value = $this->formerReverseTransform($value);

        return (array) $value;
    }

    private function formerReverseTransform($value)
    {
        switch ($this->fieldDefinition->getType()) {
            case 'checkbox':
                return (int) $value;
            case 'file':
            case 'image':
                return [
                    'filename' => $value->getPathname(),
                ];
            case 'date':
                return $value->format('Y-m-d');
            case 'datetime':
                return $value->format('Y-m-d H:i:s');
            default:
                return $value;
        }
    }
}
