<?php

declare(strict_types=1);

namespace Bolt\DataFixtures;

use Bolt\Collection\DeepCollection;
use Bolt\Configuration\Config;
use Bolt\Configuration\Content\ContentType;
use Bolt\Configuration\FileLocations;
use Bolt\Entity\Content;
use Bolt\Entity\Field;
use Bolt\Entity\User;
use Bolt\Enum\Statuses;
use Bolt\Repository\FieldRepository;
use Bolt\Utils\FakeContent;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Tightenco\Collect\Support\Collection;

class ContentFixtures extends BaseFixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /** @var Generator */
    private $faker;

    /** @var string */
    private $lastTitle = null;

    /** @var array */
    private $presetRecords = [];

    /** @var Collection */
    private $imagesIndex;

    /** @var Config */
    private $config;

    /** @var FileLocations */
    private $fileLocations;

    public function __construct(Config $config, FileLocations $fileLocations)
    {
        $this->faker = Factory::create();

        $this->presetRecords = $this->getPresetRecords();
        $this->config = $config;
        $this->fileLocations = $fileLocations;
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            TaxonomyFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['with-images', 'without-images'];
    }

    public function load(ObjectManager $manager): void
    {
        $path = $this->fileLocations->get('files')->getBasepath();
        $this->imagesIndex = $this->getImagesIndex($path);

        $this->loadContent($manager);

        $manager->flush();
    }

    private function loadContent(ObjectManager $manager): void
    {
        foreach ($this->config->get('contenttypes') as $contentType) {
            // Only add Singletons on first run, not when appending
            if ($this->getOption('--append') && $contentType['singleton']) {
                continue;
            }

            $amount = $contentType['singleton'] ? 1 : (int) ($contentType['listing_records'] * 3);

            for ($i = 1; $i <= $amount; $i++) {
                if ($i === 1) {
                    $author = $this->getReference('user_admin');
                } else {
                    $author = $this->getRandomReference('user');
                }
                /** @var User $author */
                $content = new Content();
                $content->setDefinition($contentType);
                $content->setContentType($contentType['slug']);
                $content->setAuthor($author);
                $content->setCreatedAt($this->faker->dateTimeBetween('-1 year'));
                $content->setModifiedAt($this->faker->dateTimeBetween('-1 year'));
                $content->setPublishedAt($this->faker->dateTimeBetween('-1 year'));

                $preset = $this->getPreset($contentType['slug']);

                if ($i === 1 || ! empty($preset)) {
                    $content->setStatus($preset['status'] ?? Statuses::PUBLISHED);
                } else {
                    $content->setStatus($this->getRandomStatus());
                }

                foreach ($contentType['fields'] as $name => $fieldType) {
                    $this->loadField($content, $name, $fieldType, $contentType, $preset);
                }

                foreach ($contentType['taxonomy'] as $taxonomySlug) {
                    if ($taxonomySlug === 'categories') {
                        $taxonomyAmount = 2;
                    } elseif ($taxonomySlug === 'tags') {
                        $taxonomyAmount = 4;
                    } else {
                        $taxonomyAmount = 1;
                    }

                    foreach ($this->getRandomTaxonomies($taxonomySlug, $taxonomyAmount) as $taxonomy) {
                        $content->addTaxonomy($taxonomy);
                    }
                }

                $refKey = sprintf('content_%s_%s', $contentType['slug'], $content->getSlug());
                $this->addReference($refKey, $content);

                $manager->persist($content);
            }
        }
    }

    private function loadCollectionField(Content $content, Field $field, $fieldType, ContentType $contentType, array $preset): Field
    {
        $collectionItems = $field->getDefinition()->get('fields');

        $i = 0;
        foreach ($collectionItems as $name => $type) {
            $child = $this->loadField($content, $name, $type, $contentType, $preset, false);
            $child->setParent($field);
            $child->setSortorder($i);
            $content->addField($child);
            ++$i;
        }

        return $field;
    }

    private function loadSetField(Content $content, Field $set, ContentType $contentType, array $preset): Field
    {
        $setChildren = $set->getDefinition()->get('fields');

        $children = [];
        foreach ($setChildren as $setChild => $setChildType) {
            $child = $this->loadField($content, $setChild, $setChildType, $contentType, $preset, false);
            $children[] = $child;
        }

        $set->setValue($children);

        return $set;
    }

    private function loadField(Content $content, string $name, $fieldType, ContentType $contentType, array $preset, bool $addToContent = true): Field
    {
        $sortorder = 1;

        $field = FieldRepository::factory($fieldType, $name);

        if (isset($preset[$name])) {
            $field->setValue($preset[$name]);
        } else {
            if ($fieldType['type'] === 'collection') {
                $field = $this->loadCollectionField($content, $field, $fieldType, $contentType, $preset);
            } elseif ($fieldType['type'] === 'set') {
                $field = $this->loadSetField($content, $field, $contentType, $preset);
            } else {
                $field->setValue($this->getValuesforFieldType($name, $fieldType, $contentType['singleton']));
            }
        }
        $field->setSortorder($sortorder++ * 5);

        if ($addToContent) {
            $content->addField($field);
        }

        if (isset($fieldType['localize']) && $fieldType['localize']) {
            foreach ($contentType['locales'] as $locale) {
                $field->translate($locale, false)->setValue($field->getValue());
            }
        }

        return $field;
    }

    private function getRandomStatus(): string
    {
        $statuses = ['published', 'published', 'published', 'held', 'draft', 'timed'];

        return $statuses[array_rand($statuses)];
    }

    private function getValuesforFieldType(string $name, DeepCollection $field, bool $singleton)
    {
        $nb = $singleton ? 8 : 4;

        switch ($field['type']) {
            case 'html':
                $data = FakeContent::generateHTML($nb);
                break;
            case 'markdown':
                $data = FakeContent::generateMarkdown($nb);
                break;
            case 'textarea':
                $data = $this->faker->paragraphs(3, true);
                break;
            case 'image':
                $randomImage = $this->imagesIndex->random();
                $data = [
                    'filename' => $randomImage->getRelativePathname(),
                    'alt' => $this->faker->sentence(4, true),
                    'media' => '',
                ];

                break;
            case 'file':
                $randomImage = $this->imagesIndex->random();
                $data = [
                    'filename' => $randomImage->getRelativePathname(),
                    'title' => $this->faker->sentence(4, true),
                    'media' => '',
                ];

                break;
            case 'slug':
                $data = (new Slugify())->slugify(
                    $this->lastTitle ?? $this->faker->sentence(3, true)
                );
                break;
            case 'text':
                $words = isset($field['slug']) && in_array($field['slug'], ['title', 'heading'], true) ? 3 : 7;
                $data = $this->faker->sentence($words, true);
                break;
            case 'email':
                $data = $this->faker->email;
                break;
            case 'templateselect':
                $data = [];
                break;
            case 'date':
            case 'datetime':
                $data = $this->faker->dateTime()->format('c');
                break;
            case 'number':
                $data = $this->faker->numberBetween(-100, 1000);
                break;
            case 'checkbox':
                $data = [$this->faker->numberBetween(0, 1)];

                break;
            case 'data':
                $data = [];
                for ($i = 1; $i < 5; $i++) {
                    $data[$this->faker->sentence(1)] = $this->faker->sentence(4, true);
                }
                break;
            case 'imagelist':
                $data = [];
                for ($i = 1; $i < 5; $i++) {
                    $randomImage = $this->imagesIndex->random();
                    $data[] = [
                        'filename' => $randomImage->getRelativePathname(),
                        'alt' => $this->faker->sentence(4, true),
                        'media' => '',
                    ];
                }

                break;
            case 'filelist':
                $data = [];
                for ($i = 1; $i < 5; $i++) {
                    $randomImage = $this->imagesIndex->random();
                    $data[] = [
                        'filename' => $randomImage->getRelativePathname(),
                        'title' => $this->faker->sentence(4, true),
                        'media' => '',
                    ];
                }
                break;
            case 'checkbox':
                $data = (string) rand(0, 1);
                break;
            default:
                $data = $this->faker->sentence(6, true);
        }

        if ($name === 'title' || $name === 'heading') {
            $this->lastTitle = $data;
        }

        return $data;
    }

    private function getPresetRecords(): array
    {
        $records['entries'][] = [
            'title' => 'This is a record in the "Entries" ContentType',
            'slug' => 'This is a record in the "Entries" ContentType',
        ];
        $records['blocks'][] = [
            'title' => 'About This Site',
            'slug' => 'about',
        ];
        $records['blocks'][] = [
            'title' => 'Search',
            'slug' => 'search',
        ];
        $records['blocks'][] = [
            'title' => '404 Page not found',
            'slug' => '404-not-found',
            'status' => Statuses::HELD,
        ];
        $records['blocks'][] = [
            'title' => '503 Service Unavailable (Maintenance Mode)',
            'slug' => '503-maintenance mode',
            'status' => Statuses::HELD,
        ];
        $records['tests'][] = [
            'selectfield' => 'bar',
            'multiselect' => 'Michelangelo',
            'slug' => 'title-of-the-test',
            'title' => 'Title of the test',
            'text_markup' => 'Text with <em>markup allowed</em>.',
            'text_plain' => 'Text with <strong>no</strong> markup allowed.',
            'textarea_field' => 'Textarea field with <em>simple</em> HTML in it.',
            'html_field' => 'HTML field with <em>simple</em> HTML in it.',
            'markdown_field' => 'Markdown field  with *simple* Markdown in it.',
            'text_not_sanitised' => 'Text field with <strong>markup</strong>, including <script>console.log(\'hoi\')</script>. The end.',
            'text_sanitised' => 'Text field with <strong>markup</strong>, including <script>console.log(\'hoi\')</script>. The end.',
            'attachment' => [
                'filename' => 'joey.jpg',
                'title' => $this->faker->sentence(4, true),
                'media' => '',
            ],
        ];
        $records['pages'][] = [
            'heading' => 'This is a page',
            'slug' => 'this-is-a-page',
        ];

        // Only add this fixture if the file exists: It does in the "Git Clone", but not in the
        // "Composer create-project".
        $file = dirname(dirname(__DIR__)) . '/public/theme/skeleton/custom/setcontent_1.twig';
        if (file_exists($file)) {
            $records['pages'][] = [
                'heading' => 'Setcontent test page',
                'slug' => 'Setcontent test page',
                'template' => 'custom/setcontent_1.twig',
            ];
        }

        return $records;
    }

    private function getPreset(string $slug): array
    {
        if (isset($this->presetRecords[$slug]) && ! empty($this->presetRecords[$slug]) && ! $this->getOption('--append')) {
            $preset = array_shift($this->presetRecords[$slug]);
        } else {
            $preset = [];
        }

        return $preset;
    }
}
