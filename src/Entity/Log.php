<?php

declare(strict_types=1);

namespace Bolt\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LogRepository")
 * @ORM\Table(name="log")
 * @ORM\HasLifecycleCallbacks
 */
class Log
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @ORM\Column(name="context", type="array", nullable=true)
     */
    private $context;

    /**
     * @ORM\Column(name="level", type="smallint")
     */
    private $level;

    /**
     * @ORM\Column(name="level_name", type="string", length=50)
     */
    private $levelName;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="extra", type="array", nullable=true)
     */
    private $extra;

    /**
     * @ORM\Column(name="user", type="array", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(name="location", type="array", nullable=true)
     */
    private $location;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getLevelName(): string
    {
        return $this->levelName;
    }

    public function setLevelName(string $levelName): self
    {
        $this->levelName = $levelName;
        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;
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

    public function getLocation(): ?array
    {
        return $this->location;
    }

    public function setLocation(?array $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function setUser(?array $user): self
    {
        $this->user = $user;
        return $this;
    }
}
