<?php

declare(strict_types=1);

namespace Bolt\Storage\Query;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Bolt\Storage\Query\Types\QueryType;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

class Query
{
    /** @var ContentQueryParser */
    protected $parser;

    /** @var array<string> */
    protected $scopes = [];

    protected $configuration;

    public function __construct(ContentQueryParser $parser, Config $configuration)
    {
        $this->parser = $parser;
        $this->scopes = [];
        $this->configuration = $configuration;
    }

    public function addScope(string $name, QueryScopeInterface $scope): void
    {
        $this->scopes[$name] = $scope;
    }

    public function getScope(string $name): ?QueryScopeInterface
    {
        if (array_key_exists($name, $this->scopes)) {
            return $this->scopes[$name];
        }

        return null;
    }

    /**
     * getContent based on a 'human readable query'.
     *
     * Used by the twig command {% setcontent %} but also directly.
     * For reference refer to @link https://docs.bolt.cm/templating/content-fetching
     *
     * @return QueryResultset|Content|null
     */
    public function getContent(string $textQuery, array $parameters = [])
    {
        $this->parser->setQuery($textQuery);
        $this->parser->setParameters($parameters);

        return $this->parser->fetch();
    }

    /**
     * @return QueryResultset|Content|null
     */
    public function getContentByScope(string $scopeName, string $textQuery, array $parameters = [])
    {
        $scope = $this->getScope($scopeName);
        if ($scope) {
            $this->parser->setScope($scope);
        }

        return $this->getContent($textQuery, $parameters);
    }

    /**
     * Helper to be called from Twig that is passed via a TwigRecordsView rather than the raw records.
     *
     * @param string $textQuery  The base part like `pages` or `pages/1`
     * @param array  $parameters Parameters like `printquery` and `paging`, but also `where` parameters taken from `... where { foo: bar } ...`
     */
    public function getContentForTwig(string $textQuery, array $parameters = [])
    {
        $schema = new Schema([
            'query' => new QueryType($this->configuration)
        ]);

        $result = GraphQL::executeQuery($schema, $textQuery);
        dump($result);die;
        return $this->getContentByScope('frontend', $textQuery, $parameters);
    }
}
