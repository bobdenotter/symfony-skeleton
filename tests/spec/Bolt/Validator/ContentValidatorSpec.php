<?php

declare(strict_types=1);

namespace spec\Bolt\Validator;

use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Bolt\Validator\ContentValidator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @mixin ContentValidator
 */
class ContentValidatorSpec extends ObjectBehavior
{
    function it_validates_on_empty_values(Content $content, ExecutionContextInterface $context)
    {
        $content->getCreatedAt()->willReturn(null);
        $content->getPublishedAt()->willReturn(null);
        $content->getDepublishedAt()->willReturn(null);
        $content->getModifiedAt()->willReturn(null);
        $content->getStatus()->willReturn(Statuses::DRAFT);

        $context->buildViolation(Argument::any())->shouldNotBeCalled();
        $this->validate($content, $context);
    }

    function it_validates_on_correct_values(Content $content, ExecutionContextInterface $context)
    {
        $content->getCreatedAt()->willReturn((new \DateTime())->modify('-5 days'));
        $content->getPublishedAt()->willReturn((new \DateTime())->modify('-3 days'));
        $content->getDepublishedAt()->willReturn((new \DateTime())->modify('-1 day'));
        $content->getModifiedAt()->willReturn(new \DateTime());
        $content->getStatus()->willReturn(Statuses::HELD);

        $context->buildViolation(Argument::any())->shouldNotBeCalled();
        $this->validate($content, $context);
    }

    function it_invalidates_incorrect_modification_date(Content $content, ExecutionContextInterface $context, ConstraintViolationBuilderInterface $violation)
    {
        $content->getCreatedAt()->willReturn((new \DateTime())->modify('-5 days'));
        $content->getModifiedAt()->willReturn((new \DateTime())->modify('-7 days'));
        $content->getPublishedAt()->willReturn(null);
        $content->getDepublishedAt()->willReturn(null);
        $content->getStatus()->willReturn(Statuses::DRAFT);

        $violation->atPath('modifiedAt')->shouldBeCalled()->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();
        $context->buildViolation(ContentValidator::MODIFICATION_DATE_LESS_THAN_CREATION_DATE)->shouldBeCalled()->willReturn($violation);

        $this->validate($content, $context);
    }

    function it_invalidates_incorrect_publication_date(Content $content, ExecutionContextInterface $context, ConstraintViolationBuilderInterface $violation)
    {
        $content->getCreatedAt()->willReturn((new \DateTime())->modify('-5 days'));
        $content->getPublishedAt()->willReturn((new \DateTime())->modify('-7 days'));
        $content->getModifiedAt()->willReturn(null);
        $content->getDepublishedAt()->willReturn(null);
        $content->getStatus()->willReturn(Statuses::DRAFT);

        $violation->atPath('publishedAt')->shouldBeCalled()->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();
        $context->buildViolation(ContentValidator::PUBLICATION_DATE_LESS_THAN_CREATION_DATE)->shouldBeCalled()->willReturn($violation);
        $this->validate($content, $context);
    }

    function it_invalidates_incorrect_depublication_date(Content $content, ExecutionContextInterface $context, ConstraintViolationBuilderInterface $violation)
    {
        $content->getCreatedAt()->willReturn((new \DateTime())->modify('-5 days'));
        $content->getDepublishedAt()->willReturn((new \DateTime())->modify('-7 days'));
        $content->getModifiedAt()->willReturn(null);
        $content->getPublishedAt()->willReturn(null);
        $content->getStatus()->willReturn(Statuses::DRAFT);

        $violation->atPath('depublishedAt')->shouldBeCalled()->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();
        $context->buildViolation(ContentValidator::DEPUBLICATION_DATE_LESS_THAN_CREATION_DATE)->shouldBeCalled()->willReturn($violation);
        $this->validate($content, $context);
    }

    function it_invalidates_publish_without_date(Content $content, ExecutionContextInterface $context, ConstraintViolationBuilderInterface $violation)
    {
        $content->getStatus()->willReturn(Statuses::PUBLISHED);
        $content->getPublishedAt()->willReturn(null);
        $content->getModifiedAt()->willReturn(null);
        $content->getDepublishedAt()->willReturn(null);

        $violation->atPath('publishedAt')->shouldBeCalled()->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();
        $context->buildViolation(ContentValidator::CONTENT_PUBLISHED_WITHOUT_PUBLICATION_DATE)->shouldBeCalled()->willReturn($violation);
        $this->validate($content, $context);
    }
}
