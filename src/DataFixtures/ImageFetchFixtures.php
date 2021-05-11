<?php

declare(strict_types=1);

namespace Bolt\DataFixtures;

use Bolt\Configuration\Config;
use Bolt\Configuration\FileLocations;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpClient\HttpClient;

class ImageFetchFixtures extends BaseFixture implements FixtureGroupInterface
{
    private const URL = "https://placeholder.boltcms.io/getfiles";

    /** @var FileLocations */
    private $fileLocations;

    private const AMOUNT = 20;
    private const MAX_AMOUNT = 50;

    /** @var array */
    private $curlOptions;

    public function __construct(FileLocations $fileLocations, Config $config)
    {
        $this->curlOptions = $config->get('general/curl_options')->all();

        $this->fileLocations = $fileLocations;
    }

    public static function getGroups(): array
    {
        return ['with-images'];
    }

    public function load(ObjectManager $manager): void
    {
        $path = $this->fileLocations->get('files')->getBasepath();

        // We only fetch more images, if we're currently under the MAX_AMOUNT
        if ($this->getImagesIndex($path)->count() <= self::MAX_AMOUNT) {
            $this->fetchImages();
        }
    }

    private function fetchImages(): void
    {
        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, self::AMOUNT);

        $progressBar->start();

//        for ($i = 1; $i <= self::AMOUNT; $i++) {
//            $random = $this->urls->random();
//            $url = $random[1] . random_int(10000, 99999);
//            $filename = 'image_' . random_int(10000, 99999) . '.jpg';
//
//            $client = HttpClient::create();
//            $resource = fopen($this->getOutputPath($random[0]) . $filename, 'w');
//
//            $image = $client->request('GET', $url, $this->curlOptions)->getContent();
//
//            fwrite($resource, $image);
//            fclose($resource);
//
//            $progressBar->advance();
//        }
        $client = HttpClient::create();
        $resource = fopen($this->getOutputPath() . 'placeholders.zip', 'w');


        $file = $client->request('GET', self::URL, $this->curlOptions)->getContent();
        fwrite($resource, $file);
        fclose($resource);
//

        $progressBar->finish();
        $output->writeln('');
    }

    private function getOutputPath(): string
    {
        $outputPath = $this->fileLocations->get('files')->getBasepath() . '/';

        if (! is_dir($outputPath)) {
            mkdir($outputPath);
        }

        return $outputPath;
    }
}
