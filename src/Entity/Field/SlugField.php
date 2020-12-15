<?php

declare(strict_types=1);

namespace Bolt\Entity\Field;

use Bolt\Common\Str;
use Bolt\Entity\Field;
use Bolt\Entity\FieldInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SlugField extends Field implements FieldInterface
{
    public const TYPE = 'slug';

    public function setValue($value): parent
    {
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = Str::slug($value);

        if (is_numeric($value) && $this->getDefinition()->get('allow_numeric') !== true) {
            $slug = $this->getContent()->getDefinition()->get('singular_slug');
            $value = $slug . '-' . $value;
        }

        parent::setValue([$value]);

        return $this;
    }
}
