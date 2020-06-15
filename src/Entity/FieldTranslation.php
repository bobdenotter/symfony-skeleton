<?php

declare(strict_types=1);

namespace Bolt\Entity;

use Bolt\Common\Json;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;

/**
 * @ORM\Entity
 */
class FieldTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="field_value")
     */
    protected $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): self
    {
        if (is_array($value)) {
            $this->value = Json::dump($value);
        } else {
            $this->value = (string) $value;
        }

        return $this;
    }

    public function get($key)
    {
        return isset($this->value[$key]) ? $this->value[$key] : null;
    }

    public function set(string $key, $value): self
    {
        $this->value[$key] = $value;

        return $this;
    }

    /**
     * Used to locate the translatable entity Bolt\Entity\Field in all its child classes
     * e.g. from Bolt\Entity\Field\TextField
     */
    public static function getTranslatableEntityClass(): string
    {
        return 'Field';
    }
}
