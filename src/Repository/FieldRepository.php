<?php

declare(strict_types=1);

namespace Bolt\Repository;

use Bolt\Doctrine\JsonHelper;
use Bolt\Entity\Field;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tightenco\Collect\Support\Collection;

/**
 * @method Field|null find($id, $lockMode = null, $lockVersion = null)
 * @method Field|null findOneBy(array $criteria, array $orderBy = null)
 * @method Field[]    findAll()
 * @method Field[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Field::class);
    }

    private function getQueryBuilder(?QueryBuilder $qb = null)
    {
        return $qb ?: $this->createQueryBuilder('field');
    }

    public function findOneBySlug(string $slug): ?Field
    {
        $qb = $this->getQueryBuilder();

        [$where, $slug] = JsonHelper::wrapJsonFunction('translations.value', $slug, $qb);

        return $qb
            ->innerJoin('field.translations', 'translations')
            ->addSelect('translations')
            ->andWhere($where . ' = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public static function factory(Collection $definition, string $name = '', string $label = ''): Field
    {
        $type = $definition['type'];

        $classname = '\\Bolt\\Entity\\Field\\' . ucwords($type) . 'Field';
        if (class_exists($classname)) {
            $field = new $classname();
        } else {
            $field = new Field();
        }

        if ($name !== '') {
            $field->setName($name);
        }

        $field->setDefinition($type, $definition);

        if ($label !== '') {
            $field->setLabel($label);
        }

        return $field;
    }
}
