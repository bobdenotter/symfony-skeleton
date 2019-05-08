<?php

declare(strict_types=1);

namespace Bolt\Entity\Field;

use Bolt\Entity\Field;
use Bolt\Entity\FieldInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SelectField extends Field implements FieldInterface
{
    public static function getType(): string
    {
        return 'select';
    }

    public function getValue(): ?array
    {
        if (empty($this->value)) {
            //$options = (array) $this->getDefinition()->get('values');

            // Pick the first key from array, or the full value as string, like `entries/id,title`
            // @todo wth? this is not a valid select value!
            //$this->value = [key($options)];
        }

        return $this->value;
    }
}
