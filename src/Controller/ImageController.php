<?php

declare(strict_types=1);

namespace Bolt\Controller;

use Bolt\Configuration\Config;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImageController
{
    /** @var Config */
    private $config;

    private $thumbnailOptions = ['w', 'h', 'fit'];

    /** @var Server */
    private $server;

    /** @var array */
    private $parameters = [];

    /** @var Request */
    private $request;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @Route("/thumbs/{paramString}/{filename}", methods={"GET"}, name="thumbnail", requirements={"filename"=".+"})
     */
    public function thumbnail(string $paramString, string $filename, Request $request)
    {
        if (! $this->isImage($filename)) {
            throw new NotFoundHttpException('Thumbnail not found');
        }

        $this->request = $request;

        $this->parseParameters($paramString);
        $this->createServer();
        $this->saveAsFile($paramString, $filename);

        return $this->buildResponse($filename);
    }

    private function createServer(): void
    {
        $this->server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(),
            'source' => $this->getPath(),
            'cache' => $this->getPath('cache', true, 'thumbnails'),
        ]);
    }

    private function getLocation(): string
    {
        return isset($this->parameters['location']) ? $this->parameters['location'] : $this->request->query->get('location', 'files');
    }

    private function getPath(?string $path = null, bool $absolute = true, $additional = null): string
    {
        if (! $path) {
            $path = $this->getLocation();
        }

        return $this->config->getPath($path, $absolute, $additional);
    }

    private function saveAsFile(string $paramString, string $filename): void
    {
        if (! $this->config->get('general/thumbnails/save_files', true)) {
            return;
        }

        $filesystem = new Filesystem();

        $filePath = sprintf('%s%s%s%s%s', $this->getPath('thumbs'), DIRECTORY_SEPARATOR, $paramString, DIRECTORY_SEPARATOR, $filename);
        $folderMode = $this->config->get('general/filepermissions/folders', 0775);
        $fileMode = $this->config->get('general/filepermissions/files', 0664);

        try {
            $filesystem->mkdir(dirname($filePath), $folderMode);
            $filesystem->dumpFile($filePath, $this->buildImage($filename));
            $filesystem->chmod($filePath, $fileMode);
        } catch (\Throwable $e) {
            // Fail silently, output user-friendly exception elsewhere.
        }
    }

    private function buildImage(string $filename): string
    {
        // In case we're trying to "thumbnail" an svg, just return the whole thing.
        if ($this->isSvg($filename)) {
            $filepath = sprintf('%s%s%s', $this->getPath(), DIRECTORY_SEPARATOR, $filename);

            return file_get_contents($filepath);
        }

        if ($this->request->query->has('path')) {
            $filename = sprintf('%s/%s', $this->request->query->get('path'), $filename);
        }

        $cacheFile = $this->server->makeImage($filename, $this->parameters);

        return $this->server->getCache()->read($cacheFile);
    }

    private function buildResponse(string $filename): Response
    {
        // In case we're trying to "thumbnail" an svg, just return the whole thing.
        if ($this->isSvg($filename)) {
            $filepath = sprintf('%s%s%s', $this->getPath(), DIRECTORY_SEPARATOR, $filename);

            $response = new Response(file_get_contents($filepath));
            $response->headers->set('Content-Type', 'image/svg+xml');

            return $response;
        }

        if ($this->request->query->has('path')) {
            $filename = sprintf('%s/%s', $this->request->query->get('path'), $filename);
        }

        return $this->server->getImageResponse($filename, $this->parameters);
    }

    private function parseParameters(string $paramString): void
    {
        $raw = explode('×', str_replace('x', '×', $paramString));

        $this->parameters = [
            'w' => is_numeric($raw[0]) ? (int) $raw[0] : 400,
            'h' => ! empty($raw[1]) && is_numeric($raw[1]) ? (int) $raw[1] : 300,
        ];

        foreach ($raw as $rawParameter) {
            if (mb_strpos($rawParameter, '=') !== false) {
                [$key, $value] = explode('=', $rawParameter);

                // @todo Add more thumbnailing options here, perhaps.
                if (in_array($key, $this->thumbnailOptions, true)) {
                    $this->parameters[$key] = $value;
                }
            }
        }
    }

    private function isSvg(string $filename): bool
    {
        $pathinfo = pathinfo($filename);

        return array_key_exists('extension', $pathinfo) && $pathinfo['extension'] === 'svg';
    }

    private function isImage(string $filename): bool
    {
        $pathinfo = pathinfo($filename);

        $imageExtensions = ['gif', 'png', 'jpg', 'jpeg', 'svg', 'webp'];

        return array_key_exists('extension', $pathinfo) && in_array($pathinfo['extension'], $imageExtensions, true);
    }
}
