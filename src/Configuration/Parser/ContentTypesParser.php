<?php

declare(strict_types=1);

namespace Bolt\Configuration\Parser;

use Bolt\Common\Arr;
use Bolt\Common\Str;
use Bolt\Configuration\Content\ContentType;
use Bolt\Configuration\Content\FieldType;
use Bolt\Enum\Statuses;
use Bolt\Exception\ConfigurationException;
use Cocur\Slugify\Slugify;
use Tightenco\Collect\Support\Collection;

class ContentTypesParser extends BaseParser
{
    /** @var Collection */
    private $generalConfig;

    /** @var array */
    private $localeCodes = [];

    /** @var string defaultLocale */
    private $defaultLocale;

    public function __construct(string $projectDir, Collection $generalConfig, string $defaultLocale, ?string $locales = null, string $filename = 'contenttypes.yaml')
    {
        $this->localeCodes = empty($locales) ? [] : explode('|', $locales);
        $this->generalConfig = $generalConfig;
        $this->defaultLocale = $defaultLocale;
        parent::__construct($projectDir, $filename);
    }

    /**
     * Read and parse the contenttypes.yml configuration file.
     *
     * @throws ConfigurationException
     */
    public function parse(): Collection
    {
        $contentTypes = [];
        $tempContentTypes = $this->parseConfigYaml($this->getInitialFilename());
        foreach ($tempContentTypes as $key => $contentType) {
            if (is_array($contentType)) {
                $contentType = $this->parseContentType($key, $contentType);

                if ($contentType) {
                    $contentTypes[$contentType->getSlug()] = $contentType;
                }
            }
        }

        return new Collection($contentTypes);
    }

    /**
     * Parse a single Content Type configuration array.
     *
     * @param string $key
     *
     * @throws ConfigurationException
     */
    protected function parseContentType($key, array $contentType): ?ContentType
    {
        $slugify = new Slugify();
        // If the key starts with `__`, we ignore it.
        if (mb_substr($key, 0, 2) === '__') {
            return null;
        }

        // If neither 'name' nor 'slug' is set, we need to warn the user. Same goes for when
        // neither 'singular_name' nor 'singular_slug' is set.
        if (! isset($contentType['name']) && ! isset($contentType['slug'])) {
            $error = sprintf("In content type <code>%s</code>, neither 'name' nor 'slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);

            throw new ConfigurationException($error);
        }
        if (! isset($contentType['singular_name']) && ! isset($contentType['singular_slug'])) {
            $error = sprintf("In content type <code>%s</code>, neither 'singular_name' nor 'singular_slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);

            throw new ConfigurationException($error);
        }

        // Content types without fields make no sense.
        if (! isset($contentType['fields'])) {
            $error = sprintf("In content type <code>%s</code>, no 'fields' are set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);

            throw new ConfigurationException($error);
        }

        if (! isset($contentType['slug'])) {
            $contentType['slug'] = $slugify->slugify($contentType['name']);
        }
        if (! isset($contentType['name'])) {
            $contentType['name'] = ucwords(preg_replace('/[^a-z0-9]/i', ' ', $contentType['slug']));
        }
        if (! isset($contentType['singular_slug'])) {
            $contentType['singular_slug'] = $slugify->slugify($contentType['singular_name']);
        }
        if (! isset($contentType['singular_name'])) {
            $contentType['singular_name'] = ucwords(preg_replace('/[^a-z0-9]/i', ' ', $contentType['singular_slug']));
        }
        if (! isset($contentType['show_on_dashboard'])) {
            $contentType['show_on_dashboard'] = true;
        }
        if (! isset($contentType['show_in_menu'])) {
            $contentType['show_in_menu'] = true;
        }
        if (! isset($contentType['default_status']) || ! in_array($contentType['default_status'], Statuses::all(), true)) {
            $contentType['default_status'] = Statuses::PUBLISHED;
        }
        if (! isset($contentType['viewless'])) {
            $contentType['viewless'] = false;
        }
        if (! isset($contentType['searchable'])) {
            $contentType['searchable'] = ! $contentType['viewless'];
        }

        // When Viewless it makes makes no sense to have it searchable
        if ($contentType['viewless']) {
            $contentType['searchable'] = false;
        }

        if (! isset($contentType['icon_one'])) {
            $contentType['icon_one'] = 'fa-file';
        } else {
            $contentType['icon_one'] = str_replace('fa:', 'fa-', $contentType['icon_one']);
        }
        if (! isset($contentType['icon_many'])) {
            $contentType['icon_many'] = 'fa-copy';
        } else {
            $contentType['icon_many'] = str_replace('fa:', 'fa-', $contentType['icon_many']);
        }

        if (! isset($contentType['allow_numeric_slugs'])) {
            $contentType['allow_numeric_slugs'] = false;
        }
        if (! isset($contentType['singleton'])) {
            $contentType['singleton'] = false;
        }
        if (! isset($contentType['record_template'])) {
            $contentType['record_template'] = $contentType['singular_slug'] . '.twig';
        }
        if (! isset($contentType['listing_template'])) {
            $contentType['listing_template'] = $contentType['slug'] . '.twig';
        }

        if ($contentType['singleton']) {
            $contentType['listing_records'] = 1;
        } elseif (isset($contentType['listing_records']) === false) {
            $contentType['listing_records'] = $this->generalConfig->get('listing_records');
        }

        if ($contentType['singleton']) {
            $contentType['records_per_page'] = 1;
        } elseif (isset($contentType['records_per_page']) === false) {
            $contentType['records_per_page'] = $this->generalConfig->get('records_per_page');
        }

        if (! isset($contentType['locales'])) {
            $contentType['locales'] = [];
        } else {
            $forbidden = array_diff((array) $contentType['locales'], $this->localeCodes);
            if (! empty($this->localeCodes) && ! empty($forbidden)) {
                $error = sprintf('The <code>%s</code> locale was requested, but permitted locales are <code>%s</code>. Please check your services.yaml app_locales setting.', implode(', ', $forbidden), implode(', ', $this->localeCodes));

                throw new ConfigurationException($error);
            }

            $contentType['locales'] = (array) $contentType['locales'];
        }

        [$fields, $groups] = $this->parseFieldsAndGroups($contentType['fields']);
        $contentType['fields'] = $fields;
        $contentType['groups'] = $groups;

        $contentType['order'] = $this->determineOrder($contentType);

        // Remove (deprecated) `sort` attribute.
        if (isset($contentType['sort'])) {
            unset($contentType['sort']);
        }

        // Make sure title_format is set
        if (isset($contentType['title_format'])) {
            $contentType['title_format'] = $contentType['title_format'];
        } elseif (isset($contentType['fields']['slug']['uses'])) {
            $fields = (array) $contentType['fields']['slug']['uses'];
            $contentType['title_format'] = '{' . implode('} {', $fields) . '}';
        } else {
            $contentType['title_format'] = null;
        }

        // Make sure taxonomy is an array.
        if (isset($contentType['taxonomy'])) {
            $contentType['taxonomy'] = (array) $contentType['taxonomy'];
        } else {
            $contentType['taxonomy'] = [];
        }

        // when adding relations, make sure they're added by their slug. Not their 'name' or 'singular name'.
        if (! empty($contentType['relations']) && is_array($contentType['relations'])) {
            foreach (array_keys($contentType['relations']) as $relkey) {
                if ($relkey !== $slugify->slugify($relkey)) {
                    $contentType['relations'][$slugify->slugify($relkey)] = $contentType['relations'][$relkey];
                    unset($contentType['relations'][$relkey]);
                }
            }
        } else {
            $contentType['relations'] = [];
        }

        if (! empty($contentType['relations']) || ! empty($contentType['taxonomy'])) {
            $contentType['groups'][] = 'Relations';
        }

        /** @var ContentType */
        return ContentType::deepMake($contentType);
    }

    /**
     * Parse a Content Type's field and determine the grouping.
     *
     * @throws ConfigurationException
     */
    protected function parseFieldsAndGroups(array $fields): array
    {
        $currentGroup = 'content'; // Default group name, if none was specified
        $groups = [];
        $acceptFileTypes = $this->generalConfig->get('accept_file_types');

        // Even if there's no `slug` defined, we still want to have one. We mark
        // it as 'hidden', so we don't show it when editing the Content
        if (! isset($fields['slug'])) {
            $fields['slug'] = [
                'type' => 'slug',
                'hidden' => true,
            ];
        }

        foreach ($fields as $key => $field) {
            $this->parseField($key, $field, $acceptFileTypes, $currentGroup);
            // Convert array into FieldType
            $fields[$key] = new FieldType($field, $key);
            $groups[$currentGroup] = $currentGroup;

            // Repeating fields checks
            if (isset($field['fields'])) {
                $fields[$key] = $this->parseFieldRepeaters($fields[$key], $acceptFileTypes, $currentGroup);
                if ($fields[$key] === null) {
                    unset($fields[$key]);
                }
            }
        }

        // Make sure the 'uses' of the slug is an array.
        if (isset($fields['slug']) && isset($fields['slug']['uses'])) {
            $fields['slug']['uses'] = (array) $fields['slug']['uses'];
        }

        return [$fields, $groups];
    }

    private function parseField($key, &$field, $acceptFileTypes, &$currentGroup): void
    {
        $key = str_replace('-', '_', mb_strtolower(Str::makeSafe($key, true)));
        if (! isset($field['type']) || empty($field['type'])) {
            $error = sprintf('Field "%s" has no "type" set.', $key);

            throw new ConfigurationException($error);
        }

        // If field is a "file" type, make sure the 'extensions' are set, and it's an array.
        if ($field['type'] === 'file' || $field['type'] === 'filelist') {
            if (empty($field['extensions'])) {
                $field['extensions'] = $acceptFileTypes;
            }

            $field['extensions'] = (array) $field['extensions'];
        }

        // If field is an "image" type, make sure the 'extensions' are set, and it's an array.
        if ($field['type'] === 'image' || $field['type'] === 'imagelist') {
            if (empty($field['extensions'])) {
                $extensions = new Collection(['gif', 'jpg', 'jpeg', 'png', 'svg']);
                $field['extensions'] = $extensions->intersect($acceptFileTypes)->toArray();
            }

            $field['extensions'] = (array) $field['extensions'];
        }

        // Make indexed arrays into associative for select fields
        // e.g.: [ 'yes', 'no' ] => { 'yes': 'yes', 'no': 'no' }
        if ($field['type'] === 'select' && isset($field['values']) && Arr::isIndexed($field['values'])) {
            $field['values'] = array_combine($field['values'], $field['values']);
        }

        if (empty($field['label'])) {
            $field['label'] = ucwords($key);
        }

        if (isset($field['allow_html']) === false) {
            $field['allow_html'] = in_array($field['type'], ['html', 'markdown'], true);
        }

        if (isset($field['sanitise']) === false) {
            $field['sanitise'] = in_array($field['type'], ['text', 'textarea', 'html', 'markdown'], true);
        }

        if (empty($field['group'])) {
            $field['group'] = $currentGroup;
        } else {
            $currentGroup = $field['group'];
        }

        // Initialise default_locale, if not explicit
        if (! isset($field['default_locale'])) {
            $field['default_locale'] = $this->defaultLocale;
        }
    }

    /**
     * Basic validation of repeater fields.
     *
     * @throws ConfigurationException
     */
    private function parseFieldRepeaters(FieldType $repeater, $acceptFileTypes, $currentGroup): ?FieldType
    {
        $blacklist = ['repeater', 'slug', 'templatefield', 'templateselect'];
        $whitelist = ['collection', 'set'];

        if (! isset($repeater['fields']) || ! is_array($repeater['fields']) || ! in_array($repeater['type'], $whitelist, true)) {
            return null;
        }

        $parsedRepeaterFields = [];

        foreach ($repeater['fields'] as $repeaterKey => $repeaterField) {
            if (isset($repeaterField['fields'])) {
                $repeaterField = new FieldType($repeaterField, $repeaterKey);
                $this->parseFieldRepeaters($repeaterField, $acceptFileTypes, $currentGroup);
            }

            $this->parseField($repeaterKey, $repeaterField, $acceptFileTypes, $currentGroup);

            if (isset($repeaterField['type']) && ! in_array($repeaterField['type'], $blacklist, true)) {
                $parsedRepeaterFields[$repeaterKey] = $repeaterField;
            }
        }

        $repeater['fields'] = $parsedRepeaterFields;

        return $repeater;
    }

    private function determineOrder(array $contentType): string
    {
        $order = $contentType['order'] ?? $contentType['sort'] ?? '-createdAt';

        if (is_iterable($order)) {
            $order = implode(',', $order);
        }

        $replacements = [
            'created' => 'createdAt',
            'createdat' => 'createdAt',
            'datechanged' => 'modifiedAt',
            'datecreated' => 'createdAt',
            'datepublish' => 'publishedAt',
            'modified' => 'modifiedAt',
            'modifiedat' => 'modifiedAt',
            'published' => 'publishedAt',
            'publishedat' => 'publishedAt',
            'Atat' => 'At',
            'AtAt' => 'At',
        ];

        $order = str_replace(array_keys($replacements), array_values($replacements), $order);

        $orderName = trim($order, '-');

        if (! in_array($orderName, array_keys($contentType['fields']), true) &&
            ! in_array($orderName, ['createdAt', 'modifiedAt', 'publishedAt', 'id'], true)) {
            $order = '-createdAt';
        }

        return $order;
    }
}
