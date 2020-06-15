<?php

namespace Bolt\Storage\Parser;

use Bolt\Configuration\Config;
use Bolt\Storage\Builder\ContentBuilder;
use Bolt\Storage\Builder\Filter\GraphFilter;
use Bolt\Storage\Builder\FilterFieldBuilder;
use Bolt\Storage\Builder\GraphBuilder;
use Bolt\Storage\Definition\FieldDefinition;
use Bolt\Storage\Exception\KeyValueComparatorsException;
use Bolt\Storage\Exception\UnsupportedQueryException;
use Bolt\Storage\Exception\WrongSelectionFunctionException;
use Bolt\Storage\Strategy\ContentArgumentStrategyCollection;
use DateTime;

class QueryParser
{
    private const QUERY_START_PATTERN = '/\s*query\s*/';
    private const QUERY_END_PATTERN = '/}\s*$/';
    private const MULTIPLY_WHITESPACE_PATTERN = '/\s{2,}/';
    private const QUERY_CONTENT_PATTERN = '/([a-zA-Z0-9_]+)\s{\s*([a-zA-Z0-9_\s\*]+)\s*}/';
    private const DEPRECATED_QUERY_PATTERN = '/[a-zA-Z0-9_]+(\/[a-zA-Z0-9_\-]+)?/';

    private $config;

    private $strategyCollection;

    public function __construct(Config $config, ContentArgumentStrategyCollection $strategyCollection)
    {
        $this->config = $config;
        $this->strategyCollection = $strategyCollection;
    }

    public function parseQuery(string $query, array $arguments = []): string
    {
        $textQuery = $query;
        $contents = $fields = [];

        if ($this->isQuery($textQuery)) {
            $this->removeQueryPlaceholder($textQuery);
            $this->removeMultipleWhitespaces($textQuery);

            ['contents' => $contents, 'fields' => $fields] = $this->parseQueryContent($textQuery);
        }

        if ($this->isDeprecatedQuery($textQuery)) {
            ['contents' => $contents, 'fields' => $fields] = $this->parseDeprecatedQuery(
                $textQuery,
                $arguments
            );
        }

        foreach ($contents as $index => $content) {
            $arrayFields = array_filter(explode(' ', $fields[$index]));
            if (in_array('*', $arrayFields, true) && count($fields) === 1) {
                $textQuery = preg_replace('/\*/', $this->getAllFields($content), $textQuery, 1);
            }
        }

        return $textQuery;
    }

    private function isQuery(string $textQuery): bool
    {
        return mb_strpos(ltrim($textQuery), 'query') === 0 ||
            preg_match(self::QUERY_CONTENT_PATTERN, $textQuery) === 1;
    }

    private function isDeprecatedQuery(string $textQuery): bool
    {
        return preg_match(self::DEPRECATED_QUERY_PATTERN, $textQuery) === 1;
    }

    private function removeQueryPlaceholder(string &$textQuery): void
    {
        $textQuery = trim(
            preg_replace(
                [self::QUERY_START_PATTERN, self::QUERY_END_PATTERN],
                '',
                $textQuery
            )
        );
    }

    private function removeMultipleWhitespaces(string &$textQuery): void
    {
        $textQuery = preg_replace(self::MULTIPLY_WHITESPACE_PATTERN, ' ', $textQuery);
    }

    private function parseQueryContent(string $textQuery)
    {
        preg_match_all(self::QUERY_CONTENT_PATTERN, $textQuery, $matches);

        [, $contents, $fields] = $matches;

        return [
            'contents' => $contents,
            'fields' => $fields,
        ];
    }

    private function parseDeprecatedQuery(string &$textQuery, array $arguments = []): array
    {
        $graphBuilder = new GraphBuilder();
        $contentType = $textQuery;
        $searchValue = null;
        $functionName = null;
        $functionParameter = 0;
        if (mb_strpos($textQuery, '/') !== false) {
            [$contentType, $searchValue, $functionName, $functionParameter] = $this->explodeQuery($textQuery);
        }

        $allFields = $this->getAllFields($contentType);
        $content = ContentBuilder::create($contentType)->selectFields($allFields);

        if ($searchValue !== null) {
            $content->addFilter(GraphFilter::createSimpleFilter('slug', $searchValue));
        }

        $this->extendsContentWithSelectionFunction($content, $arguments, $functionName, $functionParameter);
        $this->extendsContentByArgument($content, $arguments);

        $textQuery = $graphBuilder->addContent($content)->getQuery();

        preg_match_all(self::QUERY_CONTENT_PATTERN, $textQuery, $matches);

        [, $contents, $fields] = $matches;

        return [
            'contents' => $contents,
            'fields' => $fields,
        ];
    }

    private function extendsContentByArgument(ContentBuilder $content, array $arguments): void
    {
        foreach ($arguments as $field => $value) {
            $strategy = $this->strategyCollection->selectStrategy($field, $value);

            if ($strategy !== null) {
                $strategy->extendsByArguments($content, $field, $value);
            }
        }
    }

    private function extendsContentWithSelectionFunction(
        ContentBuilder $content,
        array $arguments,
        string $functionName,
        string $functionParameter
    ): void {
        if (empty($arguments)) {
            switch ($functionName) {
                case 'random':
                    $content->setRandom($functionParameter);
                    break;
                case 'first':
                    $content->setFirstRecords($functionParameter);
                    break;
                case 'latest':
                    $content->setLatestRecords($functionParameter);
                    break;
                default:
                    throw new WrongSelectionFunctionException($functionName);
            }
        }
    }

    private function explodeQuery(string $textQuery): array
    {
        $elements = explode('/', $textQuery);
        switch (count($elements)) {
            case 2:
                return [
                    $elements[0], $elements[1], null, null
                ];
                break;
            case 3:
                return [
                    $elements[0], null, $elements[1], $elements[2]
                ];
                break;
        }

        throw new UnsupportedQueryException($textQuery);
    }

    private function prepareFields(array $fields): string
    {
        $preparedFields = [];

        foreach ($fields as $fieldName => $fieldDefinition) {
            if (isset(FieldDefinition::SUB_FIELDS[$fieldDefinition['type']])) {
                $preparedFields[] = sprintf(
                    '%s { %s }',
                    $fieldName,
                    implode(
                        ' ',
                        FieldDefinition::SUB_FIELDS[$fieldDefinition['type']]
                    )
                );
            } else {
                $preparedFields[] = $fieldName;
            }
        }

        return implode(
            ' ',
            $preparedFields
        );
    }

    private function getAllFields(string $contentType): string
    {
        return $this->prepareFields($this->getFieldsByContentType($contentType));
    }

    private function getFieldsByContentType(string $contentType): array
    {
        return $this->config->get(sprintf('contenttypes/%s/fields', $contentType))->toArray();
    }
}
