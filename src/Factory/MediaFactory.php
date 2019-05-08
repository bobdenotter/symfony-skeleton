<?php

declare(strict_types=1);

namespace Bolt\Factory;

use Bolt\Configuration\Config;
use Bolt\Configuration\FileLocations;
use Bolt\Configuration\PathResolver;
use Bolt\Controller\UserTrait;
use Bolt\Entity\Media;
use Bolt\Repository\MediaRepository;
use Carbon\Carbon;
use PHPExif\Exif;
use PHPExif\Reader\Reader;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Tightenco\Collect\Support\Collection;
use Webmozart\PathUtil\Path;

class MediaFactory
{
    use UserTrait;

    /** @var MediaRepository */
    private $mediaRepository;

    /** @var Reader */
    private $exif;

    /** @var Collection */
    private $mediaTypes;

    /** @var FileLocations */
    private $fileLocations;

    /** @var PathResolver */
    private $pathResolver;

    public function __construct(
        PathResolver $pathResolver,
        FileLocations $fileLocations,
        MediaRepository $mediaRepository,
        TokenStorageInterface $tokenStorage,
        Config $config
    ) {
        $this->pathResolver = $pathResolver;
        $this->fileLocations = $fileLocations;
        $this->mediaRepository = $mediaRepository;
        $this->tokenStorage = $tokenStorage;
        $this->mediaTypes = $config->getMediaTypes();

        $this->exif = Reader::factory(Reader::TYPE_NATIVE);
    }

    public function createOrUpdateMedia(SplFileInfo $file, string $fileLocation, ?string $title = null): Media
    {
        $path = Path::makeRelative($file->getPath(). '/', $this->fileLocations->get($fileLocation)->getBasepath());

        $media = $this->mediaRepository->findOneBy([
            'location' => $fileLocation,
            'path' => $path,
            'filename' => $file->getFilename(),
        ]);

        if ($media === null) {
            $media = new Media();
            $media->setFilename($file->getFilename())
                ->setPath($path)
                ->setLocation($fileLocation);
        }

        if ($this->mediaTypes->contains($file->getExtension()) === false) {
            throw new UnsupportedMediaTypeHttpException("{$file->getExtension()} files are not accepted");
        }

        $media->setType($file->getExtension())
            ->setModifiedAt(Carbon::createFromTimestamp($file->getMTime()))
            ->setCreatedAt(Carbon::createFromTimestamp($file->getCTime()))
            ->setFilesize($file->getSize())
            ->setTitle($title ?? $file->getFilename())
            ->setAuthor($this->getUser());

        if ($this->isImage($media)) {
            $this->updateImageDimensions($media, $file);
        }

        return $media;
    }

    private function updateImageDimensions(Media $media, SplFileInfo $file): void
    {
        $exif = $this->exif->read($file->getRealPath());

        if ($exif instanceof Exif) {
            $media->setWidth($exif->getWidth())
                ->setHeight($exif->getHeight());

            return;
        }

        $size = @getimagesize($file->getRealpath());

        if ($size !== false) {
            $media->setWidth($size[0])
                ->setHeight($size[1]);

            return;
        }
    }

    private function isImage(Media $media): bool
    {
        return in_array($media->getType(), ['gif', 'png', 'jpg', 'svg'], true);
    }

    public function createFromFilename(string $locationName, string $path, string $filename): Media
    {
        $target = $this->pathResolver->resolve($locationName, true, [$path, $filename]);
        $file = new SplFileInfo($target, $path, $filename);

        return $this->createOrUpdateMedia($file, $locationName);
    }
}
