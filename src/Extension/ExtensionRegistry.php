<?php

declare(strict_types=1);

namespace Bolt\Extension;

use Bolt\Common\Str;
use Bolt\Event\Subscriber\ExtensionSubscriber;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use ComposerPackages\Types;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class ExtensionRegistry
{
    /** @var ExtensionInterface[] */
    protected $extensions = [];

    /** @var array */
    protected $extensionClasses = [];

    /** @var string */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @see ExtensionCompilerPass::process()
     */
    public function addCompilerPass(array $extensionClasses): void
    {
        $this->extensionClasses = array_merge($this->extensionClasses, $extensionClasses);

        $this->buildServices();
    }

    private function addComposerPackages(): void
    {
        $packages = Types::get('bolt-extension');

        /** @var PackageInterface $package */
        foreach ($packages as $package) {
            $extra = $package->getExtra();

            if (! array_key_exists('entrypoint', $extra)) {
                $message = sprintf("The extension \"%s\" has no 'extra/entrypoint' defined in its 'composer.json' file.", $package->getName());
                throw new \Exception($message);
            }

            if (! class_exists($extra['entrypoint'])) {
                $message = sprintf("The extension \"%s\" has its 'extra/entrypoint' set to \"%s\", but that class does not exist", $package->getName(), $extra['entrypoint']);
                throw new \Exception($message);
            }

            $this->extensionClasses[] = $extra['entrypoint'];
        }
    }

    private function getExtensionClasses(): array
    {
        return array_unique($this->extensionClasses);
    }

    /** @return ExtensionInterface[] */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getExtensionNames(): array
    {
        return array_keys($this->extensions);
    }

    public function getExtension(string $name): ?ExtensionInterface
    {
        if (isset($this->extensions[$name])) {
            return $this->extensions[$name];
        }

        return null;
    }

    /**
     * Runs once, invoked from the ExtensionSubscriber, to bootstrap all
     * extensions by injecting the container and running their initialize method
     *
     * @see ExtensionSubscriber::onKernelResponse()
     */
    public function initializeAll(array $objects, bool $runCli = false): void
    {
        $this->addComposerPackages();

        foreach ($this->getExtensionClasses() as $extensionClass) {
            $extension = new $extensionClass();
            $extension->injectObjects($objects);

            if (! $runCli) {
                // If we're not running on the CLI. Assumably in a browser…
                $extension->initialize();
            } elseif (method_exists($extension, 'initializeCli')) {
                // We're running on the CLI
                $extension->initializeCli();
            }

            $this->extensions[$extensionClass] = $extension;
        }
    }

    /**
     * This method calls the `getRoutes()` method for all registered extension,
     * and compiles an array of routes. This is used in
     * Bolt\Extension\RoutesLoader::load() to add all these routes to the
     * (cached) routing.
     * The reason why we're not iterating over `$this->extensions` is that when
     * this method is called, they are not instantiated yet.
     */
    public function getAllRoutes(): array
    {
        $routes = [];

        $this->addComposerPackages();

        foreach ($this->getExtensionClasses() as $extensionClass) {
            $extension = new $extensionClass();

            if (method_exists($extension, 'getRoutes')) {
                $extRoutes = $extension->getRoutes();
                $routes = array_merge($routes, $extRoutes);
            }
        }

        return $routes;
    }

    /**
     * Note: we get the composer packages here, not the ones from the compiler
     * pass. The latter already get autowired, so we need only the former. We
     * _do_ do this during the compiler pass though, since that's a good time
     * to build it.
     *
     * @see ExtensionCompilerPass::process()
     */
    public function buildServices(): void
    {
        $filename = $this->projectDir . '/config/services_bolt.yaml';

        if (file_exists($filename)) {
            return;
        }

        $services = [
            'services' => [
                '_defaults' => [
                    'autowire' => true,
                    'autoconfigure' => true,
                ],
            ],
        ];

        $packages = Types::get('bolt-extension');
        foreach ($packages as $package) {
            [$name, $service] = $this->createService($package);
            if ($name) {
                $services['services'][$name] = $service;
            }
        }

        $yaml = "# This file is auto-generated by Bolt. Do not modify.\n\n";
        $yaml .= Yaml::dump($services, 3);
        file_put_contents($filename, $yaml);
    }

    private function createService(CompletePackage $package): array
    {
        $extra = $package->getExtra();

        // If it doesn't exist, silently bail. It's handled in addComposerPackages
        if (! array_key_exists('entrypoint', $extra) || ! class_exists($extra['entrypoint'])) {
            return [false, false];
        }

        $reflection = new \ReflectionClass($extra['entrypoint']);

        $namespace = Str::removeLast($reflection->getName(), Str::splitLast($reflection->getName(), '\\'));
        $path = Path::makeRelative(dirname($reflection->getFileName()), $this->projectDir . '/foo');

        return [$namespace, [
            'resource' => $path . '/*',
            'exclude' => $path . '/{Entity,Exception}',
        ]];
    }
}
