<?php

declare(strict_types=1);

namespace Bolt\Configuration\Parser;

use Bolt\Common\Str;
use Cocur\Slugify\Slugify;
use Tightenco\Collect\Support\Collection;

class TaxonomyParser extends BaseParser
{
    public function __construct(string $projectDir, string $initialFilename = 'taxonomy.yaml')
    {
        parent::__construct($projectDir, $initialFilename);
    }

    /**
     * Read and parse the taxonomy.yml configuration file.
     */
    public function parse(): Collection
    {
        $taxonomies = $this->parseConfigYaml($this->getInitialFilename());
        $slugify = new Slugify();

        foreach ($taxonomies as $key => $taxonomy) {
            if (isset($taxonomy['name']) === false) {
                $taxonomy['name'] = ucwords(str_replace('-', ' ', Str::humanize($taxonomy['slug'])));
            }

            if (isset($taxonomy['slug']) === false) {
                $taxonomy['slug'] = $slugify->slugify($taxonomy['name']);
            }

            if (isset($taxonomy['singular_name']) === false) {
                if (isset($taxonomy['singular_slug'])) {
                    $taxonomy['singular_name'] = ucwords(str_replace('-', ' ', Str::humanize($taxonomy['singular_slug'])));
                } else {
                    $taxonomy['singular_name'] = ucwords(str_replace('-', ' ', Str::humanize($taxonomy['slug'])));
                }
            }

            if (isset($taxonomy['singular_slug']) === false) {
                $taxonomy['singular_slug'] = $slugify->slugify($taxonomy['singular_name']);
            }

            if (isset($taxonomy['has_sortorder']) === false) {
                $taxonomy['has_sortorder'] = false;
            }

            if (isset($taxonomy['allow_spaces']) === false) {
                $taxonomy['allow_spaces'] = false;
            }

            if (isset($taxonomy['required']) === false) {
                $taxonomy['required'] = false;
            }

            if (isset($taxonomy['behaves_like']) === false) {
                $taxonomy['behaves_like'] = 'tags';
            }

            if (isset($taxonomy['prefix']) === false) {
                $taxonomy['prefix'] = '';
            }

            if (isset($taxonomy['postfix']) === false) {
                $taxonomy['postfix'] = '';
            }

            if ($taxonomy['behaves_like'] === 'grouping') {
                $taxonomy['multiple'] = false;
            } elseif ($taxonomy['behaves_like'] === 'tags' || (isset($taxonomy['multiple']) && $taxonomy['multiple'] === true)) {
                $taxonomy['multiple'] = true;
            } else {
                $taxonomy['multiple'] = false;
            }

            // Make sure the options are $key => $value pairs, and not have implied integers for keys.
            if (empty($taxonomy['options']) === false && is_array($taxonomy['options'])) {
                $options = [];
                foreach ($taxonomy['options'] as $optionKey => $optionValue) {
                    if (is_numeric($optionKey)) {
                        $optionKey = $optionValue;
                        $optionValue = Str::humanize($optionValue);
                    }
                    $optionKey = $slugify->slugify($optionKey);
                    $options[$optionKey] = $optionValue;
                }
                $taxonomy['options'] = $options;
            } else {
                $taxonomy['options'] = [];
            }

            if (isset($taxonomy['behaves_like']) === false) {
                $taxonomy['behaves_like'] = 'tags';
            }

            // If taxonomy is like tags, set 'tagcloud' to true by default.
            if ($taxonomy['behaves_like'] === 'tags' && isset($taxonomy['tagcloud']) === false) {
                $taxonomy['tagcloud'] = true;
            } else {
                $taxonomy['tagcloud'] = false;
            }

            $taxonomies[$key] = $taxonomy;
        }

        return new Collection($taxonomies);
    }
}
