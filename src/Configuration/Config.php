<?php

declare(strict_types=1);

namespace Bolt\Configuration;

use Bolt\Collection\DeepCollection;
use Bolt\Common\Arr;
use Bolt\Configuration\Parser\BaseParser;
use Bolt\Configuration\Parser\ContentTypesParser;
use Bolt\Configuration\Parser\GeneralParser;
use Bolt\Configuration\Parser\MenuParser;
use Bolt\Configuration\Parser\TaxonomyParser;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Tightenco\Collect\Support\Collection;

class Config
{
    /** @var Collection */
    protected $data;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var CacheInterface */
    private $cache;

    /** @var string */
    private $projectDir;

    public function __construct(Stopwatch $stopwatch, string $projectDir, CacheInterface $cache)
    {
        $this->stopwatch = $stopwatch;
        $this->cache = $cache;
        $this->projectDir = $projectDir;
        $this->data = $this->getConfig();
    }

    private function getConfig(): Collection
    {
        $this->stopwatch->start('bolt.parseconfig');

        if ($this->validCache()) {
            $data = $this->getCache();
        } else {
            [$data, $timestamps] = $this->parseConfig();
            $this->setCache($data, $timestamps);
        }

        $this->stopwatch->stop('bolt.parseconfig');

        return $data;
    }

    private function validCache(): bool
    {
        if (! $this->cache->has('config_cache') || ! $this->cache->has('config_timestamps')) {
            return false;
        }

        $timestamps = $this->cache->get('config_timestamps');

        foreach ($timestamps as $filename => $timestamp) {
            if (file_exists($filename) === false || filemtime($filename) > $timestamp) {
                return false;
            }
        }

        return true;
    }

    private function getCache(): Collection
    {
        return $this->cache->get('config_cache');
    }

    private function setCache(Collection $data, array $timestamps): void
    {
        $this->cache->set('config_cache', $data);
        $this->cache->set('config_timestamps', $timestamps);
    }

    /**
     * Load the configuration from the various YML files.
     */
    private function parseConfig(): array
    {
        $general = new GeneralParser();

        $config = new Collection([
            'general' => $general->parse(),
        ]);

        $taxonomy = new TaxonomyParser();
        $config['taxonomies'] = $taxonomy->parse();

        $contentTypes = new ContentTypesParser($config->get('general'));
        $config['contenttypes'] = $contentTypes->parse();

        $menu = new MenuParser();
        $config['menu'] = $menu->parse();

        // @todo Add these config files if needed, or refactor them out otherwise
        //'permissions' => $this->parseConfigYaml('permissions.yml'),
        //'extensions' => $this->parseConfigYaml('extensions.yml'),

        $timestamps = $this->getConfigFilesTimestamps($general, $taxonomy, $contentTypes, $menu);

        return [
            DeepCollection::deepMake($config),
            $timestamps,
        ];
    }

    private function getConfigFilesTimestamps(BaseParser ...$configs): array
    {
        $timestamps = [];

        foreach ($configs as $config) {
            foreach ($config->getParsedFilenames() as $file) {
                $timestamps[$file] = filemtime($file);
            }
        }

        $envFilename = $this->projectDir . '/.env';
        if (file_exists($envFilename)) {
            $timestamps[$envFilename] = filemtime($envFilename);
        }

        return $timestamps;
    }

    /**
     * Get a config value, using a path.
     *
     * For example:
     * $var = $config->get('general/wysiwyg/ck/contentsCss');
     *
     * @param string|array|bool $default
     */
    public function get(string $path, $default = null)
    {
        return Arr::get($this->data, $path, $default);
    }

    public function has(string $path): bool
    {
        return Arr::has($this->data, $path);
    }

    public function getMediaTypes(): Collection
    {
        return new Collection($this->get('general/accept_media_types'));
    }
}
