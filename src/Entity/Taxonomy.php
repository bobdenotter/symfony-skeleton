<?php

declare(strict_types=1);

namespace Bolt\Entity;

use Bolt\Utils\Str;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="Bolt\Repository\TaxonomyRepository")
 */
class Taxonomy
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("public")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Bolt\Entity\Content", inversedBy="taxonomies")
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups({"get_content", "public"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups({"get_content", "public"})
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups({"get_content", "public"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups("public")
     */
    private $sortorder = 0;

    /**
     * Set dynamically during view generation
     *
     * @var string
     */
    private $link;

    public function __construct(string $type, string $slug, ?string $name = null, int $sortorder = 0)
    {
        $this->setType($type);
        $this->setName($name ?: $slug);
        $this->setSlug($slug);
        $this->setSortorder($sortorder);

        $this->content = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Content[]
     */
    public function getContent(): Collection
    {
        return $this->content;
    }

    public function addContent(Content $content): self
    {
        if (! $this->content->contains($content)) {
            $this->content[] = $content;
        }

        return $this;
    }

    public function removeContent(Content $content): self
    {
        if ($this->content->contains($content)) {
            $this->content->removeElement($content);
        }

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = Str::slug($slug);
        if (! $this->getName()) {
            $this->setName($slug);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }
}
