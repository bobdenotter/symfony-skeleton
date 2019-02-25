<?php

declare(strict_types=1);

namespace Gedmo\Translatable\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @Table(
 *         name="translation",
 *         options={"row_format":"DYNAMIC"},
 *         indexes={@Index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "field", "foreign_key"
 *         })}
 * )
 * @Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class Translation extends MappedSuperclass\AbstractTranslation
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
