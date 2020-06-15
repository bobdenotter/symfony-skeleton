<?php

declare(strict_types=1);

namespace Bolt\Entity\Field;

use Bolt\Common\Str;
use Bolt\Entity\Field;
use Bolt\Entity\FieldInterface;
use Doctrine\ORM\Mapping as ORM;
use Tightenco\Collect\Support\Collection;

/**
 * @ORM\Entity
 */
class SlugField extends Field implements FieldInterface, ScalarCastable
{
    public const TYPE = 'slug';

    public function setValue($value): parent
    {
        parent::setValue($value);

        return $this;

        /*
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = Str::slug($value);

        if (is_numeric($value)) {
            $slug = $this->getContent()->getDefinition()->get('singular_slug');
            $value = $slug . '-' . $value;
        }

        parent::setValue([$value]);

        return $this;
         */
    }

    public function getSlugUseFields(): array
    {
        return Collection::wrap($this->getDefinition()->get('uses'))->toArray();
    }
}
