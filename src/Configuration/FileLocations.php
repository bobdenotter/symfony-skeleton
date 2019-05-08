<?php

declare(strict_types=1);

namespace Bolt\Configuration;

class FileLocations
{
    /** @var array */
    private $locations = [];

    /** @var PathResolver */
    private $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
        $this->initLocations();
    }

    public function get(string $locationName): ?FileLocation
    {
        if (array_key_exists($locationName, $this->locations)) {
            return $this->locations[$locationName];
        }

        return null;
    }

    private function initLocations(): void
    {
        $this->locations = [
            'config' => new FileLocation('config', 'Configuration files', $this->pathResolver->resolve('config'), true),
            'files' => new FileLocation('files', 'Content files', $this->pathResolver->resolve('files'), false),
            'themes' => new FileLocation('themes', 'Theme files', $this->pathResolver->resolve('themes'), false),
        ];
    }
}
