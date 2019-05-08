<?php

declare(strict_types=1);

namespace Bolt\Validator;

use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ContentValidator
{
    public const MODIFICATION_DATE_LESS_THAN_CREATION_DATE = 'bolt.validation.modification_date_less_than_creation_date';
    public const CONTENT_PUBLISHED_WITHOUT_PUBLICATION_DATE = 'bolt.validation.content_published_without_publication_date';
    public const PUBLICATION_DATE_LESS_THAN_CREATION_DATE = 'bolt.validation.publication_date_less_than_creation_date';
    public const DEPUBLICATION_DATE_LESS_THAN_CREATION_DATE = 'bolt.validation.depublication_date_less_than_creation_date';

    public static function validate(Content $content, ExecutionContextInterface $context): void
    {
        if ($content->getModifiedAt() !== null && $content->getModifiedAt() < $content->getCreatedAt()) {
            $context->buildViolation(self::MODIFICATION_DATE_LESS_THAN_CREATION_DATE)
                ->atPath('modifiedAt')
                ->addViolation();
        }

        if ($content->getPublishedAt() === null && $content->getStatus() === Statuses::PUBLISHED) {
            $context->buildViolation(self::CONTENT_PUBLISHED_WITHOUT_PUBLICATION_DATE)
                ->atPath('publishedAt')
                ->addViolation();
        }

        if ($content->getPublishedAt() !== null && $content->getPublishedAt() < $content->getCreatedAt()) {
            $context->buildViolation(self::PUBLICATION_DATE_LESS_THAN_CREATION_DATE)
                ->atPath('publishedAt')
                ->addViolation();
        }

        if ($content->getDepublishedAt() !== null && $content->getDepublishedAt() < $content->getCreatedAt()) {
            $context->buildViolation(self::DEPUBLICATION_DATE_LESS_THAN_CREATION_DATE)
                ->atPath('depublishedAt')
                ->addViolation();
        }
    }
}
