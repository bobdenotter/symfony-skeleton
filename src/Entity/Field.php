<?php

declare(strict_types=1);

namespace Bolt\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Bolt\Configuration\Content\FieldType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Tightenco\Collect\Support\Collection as LaravelCollection;
use Twig\Markup;

/**
 * @ApiResource(subresourceOperations={
 *     "api_contents_fields_get_subresource"={
 *         "method"="GET",
 *         "normalization_context"={"groups"={"get_field"}}
 *     }
 * })
 * @ORM\Entity(repositoryClass="Bolt\Repository\FieldRepository")
 * @ORM\Table(
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="content_field", columns={"content_id", "name"}),
 *  })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=191)
 * @ORM\DiscriminatorMap({"generic" = "Field"})
 */
class Field implements Translatable, FieldInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups("get_field")
     */
    public $name;

    /**
     * @ORM\Column(type="json")
     * @Groups("get_field")
     * @Gedmo\Translatable
     */
    protected $value = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $sortorder = 0;

    /**
     * @Gedmo\Locale
     *
     * @var string|null
     */
    protected $locale;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $version;

    /**
     * @ORM\ManyToOne(targetEntity="Bolt\Entity\Content", inversedBy="fields")
     * @ORM\JoinColumn(nullable=false)
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="Bolt\Entity\Field")
     */
    private $parent;

    /**
     * @var ?FieldType
     */
    private $fieldTypeDefinition;

    public function __toString(): string
    {
        return implode(', ', $this->getValue());
    }

    public static function factory(LaravelCollection $definition, string $name = ''): self
    {
        $type = $definition['type'];

        $classname = '\\Bolt\\Entity\\Field\\' . ucwords($type) . 'Field';
        if (class_exists($classname)) {
            $field = new $classname();
        } else {
            $field = new self();
        }

        if ($name !== '') {
            $field->setName($name);
        }

        $field->setDefinition($type, $definition);

        return $field;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDefinition(): FieldType
    {
        if ($this->fieldTypeDefinition === null && $this->getContent()) {
            $this->setDefinitionFromContentDefinition();
        }

        return $this->fieldTypeDefinition;
    }

    private function setDefinitionFromContentDefinition(): void
    {
        $contentTypeDefinition = $this->getContent()->getDefinition();
        $this->fieldTypeDefinition = FieldType::factory($this->getName(), $contentTypeDefinition);
    }

    public function setDefinition($name, LaravelCollection $definition): void
    {
        $this->fieldTypeDefinition = FieldType::mock($name, $definition);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function get($key)
    {
        return isset($this->value[$key]) ? $this->value[$key] : null;
    }

    public function getValue(): ?array
    {
        return $this->value;
    }

    /**
     * like getValue() but returns single value for single value fields
     *
     * @return array|mixed|null
     */
    public function getParsedValue()
    {
        return static::parseValue($this->getValue());
    }

    public static function parseValue($value)
    {
        if (is_iterable($value)) {
            $count = count($value);
            if ($count === 0) {
                return null;
            } elseif ($count === 1 && array_keys($value)[0] === 0) {
                return reset($value);
            }
        }

        return $value;
    }

    /**
     * @return string|array|Markup
     */
    public function getTwigValue()
    {
        $value = $this->getParsedValue();

        if (is_string($value) && $this->getDefinition()->get('allow_html')) {
            $value = new Markup($value, 'UTF-8');
        }

        return $value;
    }

    public function setValue($value): self
    {
        $this->value = (array) $value;

        return $this;
    }

    public function getSortorder(): ?int
    {
        return $this->sortorder;
    }

    public function setSortorder(int $sortorder): self
    {
        $this->sortorder = $sortorder;

        return $this;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @Groups("get_field")
     */
    public static function getType(): string
    {
        return 'generic';
    }
}
