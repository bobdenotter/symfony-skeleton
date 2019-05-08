<?php

declare(strict_types=1);

namespace Bolt\Controller\Backend;

use Bolt\Common\Json;
use Bolt\Controller\CsrfTrait;
use Bolt\Controller\TwigAwareController;
use Bolt\Entity\Content;
use Bolt\Entity\Field;
use Bolt\Entity\Taxonomy;
use Bolt\Enum\Statuses;
use Bolt\Event\Listener\ContentFillListener;
use Bolt\Form\ContentFormType;
use Bolt\Repository\TaxonomyRepository;
use Bolt\TemplateChooser;
use Carbon\Carbon;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Tightenco\Collect\Support\Collection;

/**
 * @Security("has_role('ROLE_ADMIN')")
 */
class ContentEditController extends TwigAwareController implements BackendZone
{
    use CsrfTrait;

    /**
     * @var TaxonomyRepository
     */
    private $taxonomyRepository;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TemplateChooser
     */
    private $templateChooser;

    /**
     * @var ContentFillListener
     */
    private $contentFillListener;

    public function __construct(
        TaxonomyRepository $taxonomyRepository,
        ObjectManager $em,
        UrlGeneratorInterface $urlGenerator,
        ContentFillListener $contentFillListener,
        TemplateChooser $templateChooser,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->taxonomyRepository = $taxonomyRepository;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
        $this->contentFillListener = $contentFillListener;
        $this->templateChooser = $templateChooser;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("/new/{contentType}", name="bolt_content_new", methods={"GET"})
     */
    public function new(string $contentType, Request $request): Response
    {
        $content = $this->createContent($contentType);

        $form = $this->createCreateForm($content, $request);

        return $this->renderEdit($request, $content, $form);
    }

    /**
     * @Route("/new/{contentType}", name="bolt_content_create", methods={"POST"})
     */
    public function create(string $contentType, Request $request): Response
    {
        $content = $this->createContent($contentType);

        $form = $this->createCreateForm($content, $request);
        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->saveContentAndRedirect($content, $request);
        }

        return $this->renderEdit($request, $content, $form);
    }

    private function createContent(string $contentType): Content
    {
        $content = new Content($contentType, $this->getUser());
        $this->contentFillListener->fillContent($content);

        return $content;
    }

    private function createCreateForm(Content $content, Request $request): FormInterface
    {
        $urlParams = [
            'contentType' => $content->getContentType(),
            'edit_locale' => $this->getEditLocale($request, $content),
        ];
        $postUrl = $this->urlGenerator->generate('bolt_content_create', $urlParams);

        return $this->createForm(ContentFormType::class, $content, [
            'action' => $postUrl,
            'method' => Request::METHOD_POST,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="bolt_content_edit", methods={"GET"}, requirements={"id": "\d+"})
     */
    public function edit(Request $request, Content $content): Response
    {
        $form = $this->createEditForm($content, $request);

        return $this->renderEdit($request, $content, $form);
    }

    /**
     * @Route("/edit/{id}", name="bolt_content_save", methods={"PUT"}, requirements={"id": "\d+"})
     */
    public function save(Request $request, Content $content): Response
    {
        //$this->validateToken($request); // @todo move token validation to form itself

        $form = $this->createEditForm($content, $request);

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->saveContentAndRedirect($content, $request);
        }

        return $this->renderEdit($request, $content, $form);
    }

    private function createEditForm(Content $content, Request $request): FormInterface
    {
        $urlParams = [
            'id' => $content->getId(),
            'edit_locale' => $this->getEditLocale($request, $content),
        ];
        $postUrl = $this->urlGenerator->generate('bolt_content_save', $urlParams);

        return $this->createForm(ContentFormType::class, $content, [
            'action' => $postUrl,
            'method' => Request::METHOD_PUT,
        ]);
    }

    private function saveContentAndRedirect(Content $content, Request $request)
    {
        $this->em->persist($content);
        $this->em->flush();

        $this->addFlash('success', 'content.updated_successfully');

        $urlParams = [
            'id' => $content->getId(),
            'edit_locale' => $this->getEditLocale($request, $content),
        ];
        $url = $this->urlGenerator->generate('bolt_content_edit', $urlParams);

        // redirect to prevent form resubmission
        return new RedirectResponse($url);
    }

    private function renderEdit(Request $request, Content $content, FormInterface $form)
    {
        $twigvars = [
            'record' => $content,
            'locales' => $content->getLocales(),
            'currentlocale' => $this->getEditLocale($request, $content),
            'form' => $form->createView(),
        ];

        return $this->renderTemplate('@bolt/content/edit.html.twig', $twigvars);
    }

    /**
     * @Route("/viewsaved/{id}", name="bolt_content_viewsaved", methods={"GET"}, requirements={"id": "\d+"})
     */
    public function viewSaved(Request $request, ?Content $content = null): RedirectResponse
    {
        $urlParams = [
            'slugOrId' => $content->getId(),
            'contentTypeSlug' => $content->getDefinition()->get('slug'),
        ];

        $url = $this->urlGenerator->generate('record', $urlParams);

        return new RedirectResponse($url);
    }

    /**
     * @Route("/preview/{contentType}", name="bolt_content_preview", methods={"POST"})
     */
    public function preview(string $contentType, Request $request): Response
    {
        $content = $this->createContent($contentType);

        $form = $this->createForm(ContentFormType::class, $content, [
            'method' => Request::METHOD_POST,
        ]);
        $form->handleRequest($request);

        $recordSlug = $content->getDefinition()->get('singular_slug');

        $context = [
            'record' => $content,
            $recordSlug => $content,
        ];

        $templates = $this->templateChooser->forRecord($content);

        return $this->renderTemplate($templates, $context);
    }

    /**
     * @deprecated
     * @private made protected just to stop fixer screaming about unused private method
     */
    protected function validateToken(Request $request): void
    {
        $this->validateCsrf($request, 'editrecord');
    }

    /**
     * @deprecated
     * @private made protected just to stop fixer screaming about unused private method
     */
    protected function contentFromPost(?Content $content, Request $request): Content
    {
        $formData = $request->request->all();

        $locale = $this->getPostedLocale($formData);

        if ($content === null) {
            $content = new Content($request->attributes->get('id'), $this->getUser());
        }
        $this->contentFillListener->fillContent($content);

        // @todo dumb status validation, to be replaced with Symfony Form validation
        $status = Json::findScalar($formData['status']);
        if (in_array($status, Statuses::all(), true) === true) {
            $content->setStatus($status);
        }

        $content->setPublishedAt(new Carbon($formData['publishedAt']));
        $content->setDepublishedAt(new Carbon($formData['depublishedAt']));

        if (isset($formData['fields'])) {
            foreach ($formData['fields'] as $fieldName => $fieldValue) {
                $this->updateField($content, $fieldName, $fieldValue, $locale);
            }
        }

        if (isset($formData['taxonomy'])) {
            foreach ($formData['taxonomy'] as $fieldName => $taxonomy) {
                $this->updateTaxonomy($content, $fieldName, $taxonomy);
            }
        }

        return $content;
    }

    /**
     * @deprecated
     */
    private function updateField(Content $content, string $fieldName, $value, ?string $locale): void
    {
        if ($content->hasField($fieldName)) {
            $field = $content->getField($fieldName);
            if ($field->getDefinition()->get('localize')) {
                // load translated field
                $field->setLocale($locale);
                $this->em->refresh($field);
            }
        } else {
            $fields = $content->getDefinition()->get('fields');
            $field = Field::factory($fields->get($fieldName), $fieldName);
            $field->setName($fieldName);
            $content->addField($field);
            if ($field->getDefinition()->get('localize')) {
                $field->setLocale($locale);
            }
        }

        // If the value is an array that contains a string of JSON, parse it
        if (is_iterable($value) && Json::test(current($value))) {
            $value = Json::findArray($value);
        }

        $field->setValue($value);
    }

    /**
     * @deprecated
     */
    private function updateTaxonomy(Content $content, string $key, $taxonomy): void
    {
        $taxonomy = (new Collection(Json::findArray($taxonomy)))->filter();

        // Remove old ones
        foreach ($content->getTaxonomies($key) as $current) {
            $content->removeTaxonomy($current);
        }

        // Then (re-) add selected ones
        foreach ($taxonomy as $slug) {
            $taxonomy = $this->taxonomyRepository->findOneBy([
                'type' => $key,
                'slug' => $slug,
            ]);

            if ($taxonomy === null) {
                $taxonomy = new Taxonomy($key, $slug);
                $this->em->persist($taxonomy);
            }

            $content->addTaxonomy($taxonomy);
        }
    }

    private function getEditLocale(Request $request, Content $content): ?string
    {
        $locale = $request->query->get('edit_locale', '');
        $locales = $content->getLocales();

        if ($locales->contains($locale) === false) {
            $locale = $content->getDefaultLocale();
        }

        return $locale ?: null;
    }

    /**
     * @deprecated
     */
    private function getPostedLocale(array $post): ?string
    {
        return $post['_edit_locale'] ?: null;
    }
}
