<?php

declare(strict_types=1);

namespace Bolt\Twig;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Bolt\Entity\Relation;
use Bolt\Repository\RelationRepository;
use Bolt\Storage\Query;
use Bolt\Utils\ContentHelper;
use Tightenco\Collect\Support\Collection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RelatedExtension extends AbstractExtension
{
    /** @var RelationRepository */
    private $relationRepository;

    /** @var Config */
    private $config;

    /** @var Query */
    private $query;

    public function __construct(RelationRepository $relationRepository, Config $config, Query $query)
    {
        $this->relationRepository = $relationRepository;
        $this->config = $config;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('related', [$this, 'getRelatedContent']),
            new TwigFilter('related_by_type', [$this, 'getRelatedContentByType']),
            new TwigFilter('related_first', [$this, 'getFirstRelatedContent']),
            new TwigFilter('related_options', [$this, 'getRelatedOptions']),
            new TwigFilter('related_values', [$this, 'getRelatedValues']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('related_content', [$this, 'getRelatedContent']),
            new TwigFunction('related_content_by_type', [$this, 'getRelatedContentByType']),
            new TwigFunction('first_related_content', [$this, 'getFirstRelatedContent']),
            new TwigFunction('related_options', [$this, 'getRelatedOptions']),
            new TwigFunction('related_values', [$this, 'getRelatedValues']),
        ];
    }

    /**
     * @return array name => Content[]
     */
    public function getRelatedContentByType(Content $content, bool $bidirectional = true, ?int $limit = null, bool $publishedOnly = true): array
    {
        $relations = $this->relationRepository->findRelations($content, null, $bidirectional, $limit, $publishedOnly);

        return (new Collection($relations))
            ->reduce(function (array $result, Relation $relation) use ($content): array {
                $relatedContent = $this->extractContentFromRelation($relation, $content);
                if ($relatedContent !== null) {
                    if (isset($result[$relation->getName()]) === false) {
                        $result[$relation->getName()] = [];
                    }
                    $result[$relation->getName()][] = $relatedContent;
                }

                return $result;
            }, []);
    }

    /**
     * @return Content[]
     */
    public function getRelatedContent(Content $content, ?string $name = null, bool $bidirectional = true, ?int $limit = null, bool $publishedOnly = true): array
    {
        $relations = $this->relationRepository->findRelations($content, $name, $bidirectional, $limit, $publishedOnly);

        return (new Collection($relations))
            ->map(function (Relation $relation) use ($content) {
                return $this->extractContentFromRelation($relation, $content);
            })
            ->filter()
            ->toArray();
    }

    public function getFirstRelatedContent(Content $content, ?string $name = null, bool $bidirectional = true, bool $publishedOnly = true): ?Content
    {
        $relation = $this->relationRepository->findFirstRelation($content, $name, $bidirectional, $publishedOnly);

        if ($relation === null) {
            return null;
        }

        return $this->extractContentFromRelation($relation, $content);
    }

    private function extractContentFromRelation(Relation $relation, Content $source): ?Content
    {
        if ($relation->getFromContent()->getId() === $source->getId()) {
            return $relation->getToContent();
        } elseif ($relation->getToContent()->getId() === $source->getId()) {
            return $relation->getFromContent();
        }

        return null;
    }

    public function getRelatedOptions(string $contentTypeSlug, ?string $order = null, string $format = '', ?bool $required = false): Collection
    {
        $maxAmount = $this->config->get('maximum_listing_select', 1000);

        $contentType = $this->config->getContentType($contentTypeSlug);

        if (! $order) {
            $order = $contentType->get('order');
        }

        $pager = $this->query->getContent($contentTypeSlug, ['order' => $order])
            ->setMaxPerPage($maxAmount)
            ->setCurrentPage(1);

        $records = iterator_to_array($pager->getCurrentPageResults());

        $options = [];

        // We need to add this as a 'dummy' option for when the user is allowed
        // not to pick an option. This is needed, because otherwise the `select`
        // would default to the first one.
        if ($required === false) {
            $options[] = [
                'key' => '',
                'value' => '',
            ];
        }

        /** @var Content $record */
        foreach ($records as $record) {
            $options[] = [
                'key' => $record->getId(),
                'value' => ContentHelper::get($record, $format),
            ];
        }

        return new Collection($options);
    }

    public function getRelatedValues(Content $source, string $contentType): Collection
    {
        if ($source->getId() === null) {
            return new Collection([]);
        }

        $content = $this->getRelatedContent($source, $contentType, true, null, false);

        $values = [];

        /** @var Content $record */
        foreach ($content as $record) {
            $values[] = $record->getId();
        }

        return new Collection($values);
    }
}
