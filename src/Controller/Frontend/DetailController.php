<?php

declare(strict_types=1);

namespace Bolt\Controller\Frontend;

use Bolt\Configuration\Config;
use Bolt\Controller\TwigAwareController;
use Bolt\Enum\Statuses;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\FieldRepository;
use Bolt\TemplateChooser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class DetailController extends TwigAwareController
{
    /**
     * @var TemplateChooser
     */
    private $templateChooser;

    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    public function __construct(
        Config $config,
        Environment $twig,
        TemplateChooser $templateChooser,
        ContentRepository $contentRepository,
        FieldRepository $fieldRepository
    ) {
        $this->templateChooser = $templateChooser;
        $this->contentRepository = $contentRepository;
        $this->fieldRepository = $fieldRepository;
        parent::__construct($config, $twig);
    }

    /**
     * @Route(
     *     "/{contentTypeSlug}/{slugOrId}",
     *     name="record",
     *     requirements={"contentTypeSlug"="%bolt.requirement.contenttypes%"},
     *     methods={"GET"})
     *
     * @param string|int $slugOrId
     */
    public function record($slugOrId, ?string $contentTypeSlug = null): Response
    {
        // @todo should we check content type?
        if (is_numeric($slugOrId)) {
            $record = $this->contentRepository->findOneBy(['id' => (int) $slugOrId]);
        } else {
            // @todo this should search only by slug or any other unique field
            $field = $this->fieldRepository->findOneBySlug($slugOrId);
            if ($field === null) {
                throw new NotFoundHttpException('Content does not exist.');
            }
            $record = $field->getContent();
        }

        if ($record->getStatus() !== Statuses::PUBLISHED) {
            throw new NotFoundHttpException('Content is not published');
        }

        $recordSlug = $record->getDefinition()->get('singular_slug');

        $context = [
            'record' => $record,
            $recordSlug => $record,
        ];

        dump($record);
        dump($record->getFieldValues());

        $templates = $this->templateChooser->forRecord($record);

        return $this->renderTemplate($templates, $context);
    }
}
