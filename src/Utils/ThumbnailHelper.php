<?php

declare(strict_types=1);

namespace Bolt\Utils;

use Bolt\Common\Str;
use Bolt\Configuration\Config;

class ThumbnailHelper
{
    /** @var Config */
    private $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config;
    }

    private function parameters(?int $width = null, ?int $height = null, ?string $fit = null, ?string $location = null): string
    {
        if (! $width && ! $height) {
            $width = $this->config->get('general/thumbnails/default_thumbnail/0', 320);
            $height = $this->config->get('general/thumbnails/default_thumbnail/1', 240);
        } elseif (! $width) {
            // If no width, let it be ridiculously high, so that it crops based on height
            $width = 10000;
        } elseif (! $height) {
            // If no height, let it be ridiculously high, so that it crops based on width
            $height = 10000;
        }

        if ($location === 'files') {
            $location = null;
        }

        return implode('×', array_filter([$width, $height, $fit, $location]));
    }

    public function path(?string $filename = null, ?int $width = null, ?int $height = null, ?string $location = null, ?string $path = null, ?string $fit = null): string
    {
        if (! $filename) {
            return $this->config->get('general/thumbnails/notfound_image', '/assets/images/placeholder.png');
        }

        if ($path) {
            $filename = $path . '/' . $filename;
        }

        $paramString = $this->parameters($width, $height, $fit, $location);
        $filename = Str::ensureStartsWith($filename, '/');

        return sprintf('/thumbs/%s%s', $paramString, $filename);
    }
}
