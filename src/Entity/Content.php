<?php

declare(strict_types=1);

namespace Bolt\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Bolt\Content\ContentType;
use Bolt\Enum\Statuses;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Tightenco\Collect\Support\Collection as LaravelCollection;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"get_content"}},
 *     collectionOperations={"get"},
 *     itemOperations={"get"}
 * )
 * @ApiFilter(SearchFilter::class)
 * @ORM\Entity(repositoryClass="Bolt\Repository\ContentRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="content_type_idx", columns={"content_type"}),
 *     @ORM\Index(name="status_idx", columns={"status"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Content implements \JsonSerializable
{
    use ContentLocalizeTrait;
    use ContentExtrasTrait;

    public const NUM_ITEMS = 8; // @todo This can't be a const

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("get_content")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Groups("get_content")
     */
    private $contentType;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Bolt\Entity\User", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("put")
     */
    private $author;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     * @Groups("put")
     */
    private $status = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"get_content", "put"})
     */
    private $modifiedAt = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"get_content", "put"})
     */
    private $publishedAt = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("put")
     */
    private $depublishedAt = null;

    /**
     * @var Collection|Field[]
     *
     * @Groups({"put"})
     * @MaxDepth(1)
     * @ORM\OneToMany(
     *     targetEntity="Bolt\Entity\Field",
     *     mappedBy="content",
     *     indexBy="name",
     *     fetch="EAGER",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     * @ORM\OrderBy({"sortorder": "ASC"})
     */
    private $fields;

    /**
     * @var ContentType|null
     */
    private $contentTypeDefinition;

    /**
     * @var Collection|Taxonomy[]
     * @Groups({"put"})
     * @MaxDepth(1)
     *
     * @ORM\ManyToMany(targetEntity="Bolt\Entity\Taxonomy", mappedBy="content", cascade={"persist"})
     */
    private $taxonomies;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->taxonomies = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    public function __toString(): string
    {
        $contentName = $this->getDefinition() ? $this->getContentTypeName() : 'Content';
        if ($this->getId()) {
            return sprintf('%s #%d', $contentName, $this->getId());
        }
        return sprintf('New %s', $contentName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @see: Bolt\EventListener\ContentFillListener
     */
    public function setDefinitionFromContentTypesConfig(LaravelCollection $contentTypesConfig): void
    {
        $this->contentTypeDefinition = ContentType::factory($this->contentType, $contentTypesConfig);
    }

    public function getDefinition(): ?ContentType
    {
        return $this->contentTypeDefinition;
    }

    public function getSlug(): ?string
    {
        return $this->getFieldValue('slug');
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentTypeSlug(): string
    {
        if ($this->getDefinition() === null) {
            throw new \RuntimeException('Content not fully initialized');
        }

        return $this->getDefinition()->get('singular_slug');
    }

    public function getContentTypeName(): string
    {
        if ($this->getDefinition() === null) {
            throw new \RuntimeException('Content not fully initialized');
        }

        return $this->getDefinition()->get('singular_name') ?: $this->getContentTypeSlug();
    }

    public function getIcon(): ?string
    {
        if ($this->getDefinition() === null) {
            throw new \RuntimeException('Content not fully initialized');
        }

        return $this->getDefinition()->get('icon_one') ?: $this->getDefinition()->get('icon_many');
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): void
    {
        $this->author = $author;
    }

    public function getStatus(): ?string
    {
        if (Statuses::isValid($this->status) === false) {
            $this->status = $this->getDefinition()->get('default_status');
        }

        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (Statuses::isValid($status)) {
            $this->status = $status;
        }

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTime $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getDepublishedAt(): ?\DateTime
    {
        return $this->depublishedAt;
    }

    public function setDepublishedAt(?\DateTime $depublishedAt): self
    {
        $this->depublishedAt = $depublishedAt;

        return $this;
    }

    /**
     * @return Collection|Field[]
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @Groups("get_content")
     */
    public function getFieldValues(): array
    {
        $fieldValues = [];
        foreach ($this->getFields() as $field) {
            $fieldValues[$field->getName()] = $field->getFlattenedValue();
        }

        return $fieldValues;
    }

    /**
     * @Groups("get_content")
     */
    public function getTaxonomyValues(): array
    {
        $taxonomyValues = [];
        foreach ($this->getTaxonomies() as $taxonomy) {
            if (isset($taxonomyValues[$taxonomy->getType()]) === false) {
                $taxonomyValues[$taxonomy->getType()] = [];
            }
            $taxonomyValues[$taxonomy->getType()][$taxonomy->getSlug()] = $taxonomy->getName();
        }

        return $taxonomyValues;
    }

    /**
     * @return array|mixed|null
     */
    public function getFieldValue(string $fieldName)
    {
        if ($this->hasField($fieldName) === false) {
            return null;
        }

        return $this->getField($fieldName)->getFlattenedValue();
    }

    public function getField(string $fieldName): Field
    {
        if ($this->hasField($fieldName) === false) {
            throw new \InvalidArgumentException(sprintf("Content does not have '%s' field", $fieldName));
        }

        return $this->fields[$fieldName];
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    public function hasFieldDefined(string $fieldName): bool
    {
        return $this->contentTypeDefinition->get('fields')->has($fieldName);
    }

    public function addField(Field $field): self
    {
        if ($this->hasField($field->getName())) {
            throw new \InvalidArgumentException(sprintf("Content already has '%s' field", $field->getName()));
        }

        $this->fields[$field->getName()] = $field;
        $field->setContent($this);

        return $this;
    }

    public function removeField(Field $field): self
    {
        unset($this->fields[$field->getName()]);

        // set the owning side to null (unless already changed)
        if ($field->getContent() === $this) {
            $field->setContent(null);
        }

        return $this;
    }

    /**
     * @Groups("get_content")
     */
    public function getAuthorName(): string
    {
        return $this->getAuthor()->getDisplayName();
    }

    public function getStatuses(): array
    {
        return Statuses::all();
    }

    public function getStatusOptions(): array
    {
        $options = [];

        foreach (Statuses::all() as $option) {
            $options[] = [
                'key' => $option,
                'value' => ucwords($option),
                'selected' => $option === $this->getStatus(),
            ];
        }

        return $options;
    }

    /**
     * @return Collection|Taxonomy[]
     */
    public function getTaxonomies(?string $type = null): Collection
    {
        if ($type) {
            return $this->taxonomies->filter(
                function (Taxonomy $taxonomy) use ($type) {
                    return $taxonomy->getType() === $type;
                }
            );
        }

        return $this->taxonomies;
    }

    public function addTaxonomy(Taxonomy $taxonomy): self
    {
        if ($this->taxonomies->contains($taxonomy) === false) {
            $this->taxonomies[] = $taxonomy;
            $taxonomy->addContent($this);
        }

        return $this;
    }

    public function removeTaxonomy(Taxonomy $taxonomy): self
    {
        if ($this->taxonomies->contains($taxonomy)) {
            $this->taxonomies->removeElement($taxonomy);
            $taxonomy->removeContent($this);
        }

        return $this;
    }

    /**
     * Generic getter for a record fields. Will return the field with $name.
     *
     * - {{ record.title }} => field named title
     * - {{ record|title }} => value of guessed title field
     * - {{ record.image }} => field named image
     * - {{ record|image }} => value of guessed image field
     */
    public function __call(string $name, array $arguments = []): Field
    {
        try {
            return $this->getField($name);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(sprintf('Invalid field name or method call on %s: %s', $this->__toString(), $name));
        }
    }
}
