<?php

declare(strict_types=1);

namespace Bolt\Tests\Repository;

use Bolt\Entity\Taxonomy;
use Bolt\Tests\DbAwareTestCase;

/**
 * @todo Add represenative tests here, when methods are implemented in TaxonomyRepository
 */
class TaxonomyRepositoryTest extends DbAwareTestCase
{
    protected function setUp(): void
    {
        $this->markTestIncomplete("This test takes like forever to run so let's skip it until it will be finished");

        parent::setUp();

        // fixtures loading takes a lot of time, it would be better to load database dump for tests
        self::runCommand('doctrine:fixtures:load --no-interaction');
    }

    public function testSearchByType(): void
    {
        $taxonomies = $this->getEm()
            ->getRepository(Taxonomy::class)
            ->findBy(['type' => 'groups']);

        $this->assertCount(3, $taxonomies);
    }

    public function testSearchBySlug(): void
    {
        $taxonomies = $this->getEm()
            ->getRepository(Taxonomy::class)
            ->findBy(['slug' => 'fun']);

        $this->assertCount(2, $taxonomies);
    }

    public function testSearchByName(): void
    {
        $taxonomies = $this->getEm()
            ->getRepository(Taxonomy::class)
            ->findBy(['name' => 'Movies']);

        $this->assertCount(1, $taxonomies);
    }

    public function testPersistEntity(): void
    {
        $taxonomy = new Taxonomy('foo', 'bar');

        $this->getEm()->persist($taxonomy);
        $this->getEm()->flush();

        $taxonomies = $this->getEm()
            ->getRepository(Taxonomy::class)
            ->findBy(['type' => 'foo']);

        $this->assertCount(1, $taxonomies);

        $this->getEm()->remove($taxonomy);
        $this->getEm()->flush();

        $taxonomies = $this->getEm()
            ->getRepository(Taxonomy::class)
            ->findBy(['type' => 'foo']);

        $this->assertCount(0, $taxonomies);
    }
}
