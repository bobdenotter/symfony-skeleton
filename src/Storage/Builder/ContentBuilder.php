<?php

declare(strict_types=1);

namespace Bolt\Storage\Builder;

use Bolt\Storage\Builder\Filter\GraphFilter;
use Bolt\Storage\Exception\WrongConditionConnectionException;
use Exception;

class ContentBuilder implements GraphBuilderInterface
{
    private $contentName;

    private $firstRecords = 0;

    private $latestRecords = 0;

    private $randomRecords = 0;

    private $limit = 10;

    private $order = [];

    /**
     * @var array<GraphFilter>
     */
    private $filters = [];

    private $fields = [];

    public function __construct(string $contentName)
    {
        $this->contentName = $contentName;
    }

    public static function create(string $contentName): self
    {
        return new self($contentName);
    }

    public function setFirstRecords(int $firstRecords): self
    {
        $this->firstRecords = $firstRecords;

        return $this;
    }

    public function setLatestRecords(int $latestRecords): self
    {
        $this->latestRecords = $latestRecords;

        return $this;
    }

    public function setRandom(int $randomRecords): self
    {
        $this->randomRecords = $randomRecords;

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOrder(string $fieldName, string $orderType = 'ASC'): self
    {
        $this->order[] = [$fieldName, $orderType];

        return $this;
    }

    public function addOrder(string $fieldName, string $orderType = 'ASC'): self
    {
        $this->order[] = [$fieldName, $orderType];

        return $this;
    }

    public function addFilter(GraphFilter $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function selectFields(...$fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getQuery(): string
    {
        $fields = implode(' ', $this->fields);
        if (empty($this->getCondition())) {
            return sprintf('%s { %s }', $this->contentName, $fields);
        }

        return sprintf('%s (%s) { %s }', $this->contentName, $this->getCondition(), $fields);
    }

    private function getCondition(): string
    {
        $conditions = [];

        if ($this->firstRecords !== 0 && $this->latestRecords !== 0) {
            throw new WrongConditionConnectionException();
        }

        if ($this->firstRecords > 0) {
            $conditions[] = sprintf('first: %d', $this->firstRecords);
        }

        if ($this->latestRecords > 0) {
            $conditions[] = sprintf('latest: %d', $this->latestRecords);
            $conditions[] = sprintf('order: {field: "%s", direction: "%s"}', 'id', 'DESC');
        }

        if ($this->randomRecords > 0) {
            $conditions[] = sprintf('random: %d', $this->randomRecords);
        }

        if ($this->latestRecords === 0 && empty($this->order) === false) {
            $fields = [];
            $directions = [];
            foreach ($this->order as $order) {
                [$field, $direction] = $order;
                $directions[] = $direction;
                $fields[] = $field;
            }
            $conditions[] = sprintf(
                'order: {field: "%s", direction: "%s"}',
                implode(',', $fields),
                implode(',', $directions)
            );
        }

        if (empty($this->filters) === false) {
            $filterCount = count($this->filters);
            switch ($filterCount) {
                case 1:
                    $conditions[] = sprintf('filter: %s', implode(',', $this->filters));
                    break;
                default:
                    $conditions[] = sprintf('filter: {%s}', implode(',', $this->filters));
                    break;
            }
        }

        if ($this->limit !== 10) {
            $conditions[] = sprintf('limit: %d', $this->limit);
        }

        return implode(', ', $conditions);
    }
}
