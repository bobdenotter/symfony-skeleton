<?php

declare(strict_types=1);

namespace Bolt\Storage\Builder;

class GraphBuilder implements GraphBuilderInterface
{
    /**
     * @var array<ContentBuilder>
     */
    private $contents;

    public static function createQuery(): self
    {
        return new self();
    }

    public function addContent(...$contentBuilders): self
    {
        $this->contents = $contentBuilders;

        return $this;
    }

    public function getQuery(): string
    {
        $query = 'query { %s }';
        $queries = [];
        /** @var GraphBuilderInterface $content */
        foreach ($this->contents as $content) {
            $queries[] = $content->getQuery();
        }

        return sprintf($query, implode(' ', $queries));
    }
}
