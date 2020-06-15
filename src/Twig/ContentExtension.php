<?php

declare(strict_types=1);

namespace Bolt\Twig;

use Bolt\Canonical;
use Bolt\Configuration\Config;
use Bolt\Configuration\Content\ContentType;
use Bolt\Entity\Content;
use Bolt\Entity\Field\Excerptable;
use Bolt\Entity\Field\ImageField;
use Bolt\Entity\Field\ImagelistField;
use Bolt\Entity\Field\SelectField;
use Bolt\Entity\Field\TemplateselectField;
use Bolt\Enum\Statuses;
use Bolt\Log\LoggerTrait;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\TaxonomyRepository;
use Bolt\Storage\Query;
use Bolt\Utils\ContentHelper;
use Bolt\Utils\Excerpt;
use Bolt\Utils\Html;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tightenco\Collect\Support\Collection;
use Tightenco\Collect\Support\Collection as LaravelCollection;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    use LoggerTrait;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ContentRepository */
    private $contentRepository;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var Security */
    private $security;

    /** @var Request */
    private $request;

    /** @var Config */
    private $config;

    /** @var Query */
    private $query;

    /** @var TaxonomyRepository */
    private $taxonomyRepository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Canonical */
    private $canonical;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ContentRepository $contentRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        Security $security,
        RequestStack $requestStack,
        Config $config,
        Query $query,
        TaxonomyRepository $taxonomyRepository,
        TranslatorInterface $translator,
        Canonical $canonical
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->contentRepository = $contentRepository;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->security = $security;
        $this->request = $requestStack->getCurrentRequest();
        $this->config = $config;
        $this->query = $query;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->translator = $translator;
        $this->canonical = $canonical;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        $safe = [
            'is_safe' => ['html'],
        ];
        $env = ['needs_environment' => true];

        return [
            new TwigFilter('title', [$this, 'getTitle'], $safe),
            new TwigFilter('title_fields', [$this, 'getTitleFields']),
            new TwigFilter('image', [$this, 'getImage']),
            new TwigFilter('excerpt', [$this, 'getExcerpt'], $safe),
            new TwigFilter('previous', [$this, 'getPreviousContent']),
            new TwigFilter('next', [$this, 'getNextContent']),
            new TwigFilter('current', [$this, 'isCurrent'], $env),
            new TwigFilter('link', [$this, 'getLink'], $safe),
            new TwigFilter('edit_link', [$this, 'getEditLink']),
            new TwigFilter('taxonomies', [$this, 'getTaxonomies']),
            new TwigFilter('has_path', [$this, 'hasPath']),
            new TwigFilter('allow_twig', [$this, 'allowTwig'], $env),
            new TwigFilter('status_options', [$this, 'statusOptions']),
            new TwigFilter('feature', [$this, 'getSpecialFeature']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        $safe = [
            'is_safe' => ['html'],
        ];
        $env = ['needs_environment' => true];

        return [
            new TwigFunction('excerpt', [$this, 'getExcerpt'], $safe),
            new TwigFunction('previous_record', [$this, 'getPreviousContent']),
            new TwigFunction('next_record', [$this, 'getNextContent']),
            new TwigFunction('list_templates', [$this, 'getListTemplates']),
            new TwigFunction('pager', [$this, 'pager'], $env + $safe),
            new TwigFunction('select_options', [$this, 'selectOptions']),
            new TwigFunction('taxonomy_options', [$this, 'taxonomyOptions']),
            new TwigFunction('taxonomy_values', [$this, 'taxonomyValues']),
            new TwigFunction('icon', [$this, 'icon'], $safe),
        ];
    }

    public function getAnyTitle(Content $content, int $length = 120): string
    {
        $title = $this->getTitle($content, '', $length);

        if (! empty($title)) {
            return $title;
        }

        if ($content->getDefinition()->has('locales')) {
            $locales = $content->getDefinition()->get('locales');

            foreach ($locales as $locale) {
                $title = $this->getTitle($content, $locale, $length);

                if (! empty($title)) {
                    return $title;
                }
            }
        }

        return '(untitled)';
    }

    public function getTitle(Content $content, string $locale = '', int $length = 120): string
    {
        if (empty($locale)) {
            $locale = $this->request->getLocale();
        }

        if (ContentHelper::isSuitable($content)) {
            $title = ContentHelper::get($content, $content->getDefinition()->get('title_format'), $locale);
        } else {
            $title = ContentHelper::getFieldBasedTitle($content, $locale);
        }

        return Html::trimText($title, $length);
    }

    public function getTitleFields(Content $content): array
    {
        if (ContentHelper::isSuitable($content)) {
            return ContentHelper::getFieldNames($content->getDefinition()->get('title_format'));
        }

        return ContentHelper::guessTitleFields($content);
    }

    /**
     * @return ImageField|array|null
     */
    public function getImage(?Content $content, bool $onlyValues = false)
    {
        if (! $content) {
            return null;
        }

        foreach ($content->getFields() as $field) {
            if ($field instanceof ImageField && $field->get('filename')) {
                return $onlyValues ? $field->getValue() : $field;
            }

            if ($field instanceof ImagelistField) {
                $firstImage = current($field->getValue());
                if ($firstImage && $firstImage->get('filename')) {
                    return $onlyValues ? $firstImage->getValue() : $firstImage;
                }
            }
        }

        return null;
    }

    /**
     * @param string|Markup|Content $content
     * @param string|array|null     $focus
     */
    public function getExcerpt($content, int $length = 280, bool $includeTitle = false, $focus = null): string
    {
        if (is_string($content) || $content instanceof Markup) {
            return Excerpt::getExcerpt((string) $content, $length);
        }

        if (ContentHelper::isSuitable($content, 'excerpt_format')) {
            $excerpt = ContentHelper::get($content, $content->getDefinition()->get('excerpt_format'));
        } else {
            $excerpt = $this->getFieldBasedExcerpt($content, $length, $includeTitle);
        }

        return Excerpt::getExcerpt(rtrim($excerpt, '. '), $length, $focus);
    }

    private function getFieldBasedExcerpt(Content $content, int $length, bool $includeTitle = false): string
    {
        $excerptParts = [];

        if ($includeTitle) {
            $title = $this->getTitle($content);
            if ($title !== '') {
                $title = Html::trimText($title, $length);
                $excerptParts[] = $title;
            }
        }

        $skipFields = $this->getTitleFields($content);

        foreach ($content->getFields() as $field) {
            if ($field instanceof Excerptable && in_array($field->getName(), $skipFields, true) === false) {
                $excerptPart = $field->__toString();
                if ($excerptPart !== '') {
                    $excerptParts[] = $excerptPart;
                }
            }
        }

        $specialChars = ['.', ',', '!', '?'];
        $excerpt = array_reduce($excerptParts, function (string $excerpt, string $part) use ($specialChars): string {
            if (in_array(mb_substr($part, -1), $specialChars, true) === false) {
                // add period at end of string if it doesn't have sentence end
                $part .= '.';
            }

            return $excerpt . $part . ' ';
        }, '');

        return rtrim($excerpt, '. ');
    }

    public function getPreviousContent(Content $content, string $byColumn = 'id', bool $sameContentType = true): ?Content
    {
        return $this->getAdjacentContent($content, 'previous', $byColumn, $sameContentType);
    }

    public function getNextContent(Content $content, string $byColumn = 'id', bool $sameContentType = true): ?Content
    {
        return $this->getAdjacentContent($content, 'next', $byColumn, $sameContentType);
    }

    private function getAdjacentContent(Content $content, string $direction, string $byColumn = 'id', bool $sameContentType = true): ?Content
    {
        if ($byColumn !== 'id') {
            // @todo implement ordering by other columns/fields too
            throw new \RuntimeException('Ordering content by column other than ID is not yet implemented');
        }

        $byColumn = filter_var($byColumn, FILTER_SANITIZE_STRING);
        $contentType = $sameContentType ? $content->getContentType() : null;

        return $this->contentRepository->findAdjacentBy($byColumn, $direction, $content->getId(), $contentType);
    }

    public function isCurrent(Environment $env, Content $content): bool
    {
        // If we have a $record set in the Global Twig env, we can simply
        // compare that to what's passed in.
        if (array_key_exists('record', $env->getGlobals())) {
            return $env->getGlobals()['record'] === $content;
        }

        // Otherwise, we'll have to compare 'slugOrId' and 'contentTypeSlug' as
        // grabbed from the Request
        $recordParams = [
            'slugOrId' => $content->getSlug() ?: $content->getId(),
            'contentTypeSlug' => $content->getContentTypeSingularSlug(),
        ];

        $routeParams = $this->request->get('_route_params');

        return isset($routeParams['slugOrId']) &&
            isset($routeParams['contentTypeSlug']) &&
            $recordParams['slugOrId'] === $routeParams['slugOrId'] &&
            $recordParams['contentTypeSlug'] === $routeParams['contentTypeSlug'];
    }

    public function allowTwig(Environment $env, Content $content): void
    {
        $content->setTwig($env);
    }

    public function getLink(Content $content, bool $canonical = false, string $locale = ''): ?string
    {
        if ($content->getId() === null || $content->getDefinition()->get('viewless')) {
            return null;
        }

        if (empty($locale)) {
            $locale = $this->request->getLocale();
        }

        if ($this->isHomepage($content)) {
            return $this->generateLink('homepage_locale', ['_locale' => $locale], $canonical);
        }

        $params = [
            '_locale' => $locale,
            'slugOrId' => $content->getSlug() ?: $content->getId(),
            'contentTypeSlug' => $content->getContentTypeSingularSlug(),
        ];

        return $this->generateLink('record_locale', $params, $canonical);
    }

    public function getEditLink(Content $content): ?string
    {
        if ($content->getId() === null || ! $this->security->getUser()) {
            return null;
        }

        return $this->generateLink('bolt_content_edit', ['id' => $content->getId()]);
    }

    public function getDeleteLink(Content $content, bool $absolute = false): ?string
    {
        if ($content->getId() === null || ! $this->security->getUser()) {
            return null;
        }

        $params = [
            'id' => $content->getId(),
            'token' => (string) $this->csrfTokenManager->getToken('delete'),
        ];

        return $this->generateLink('bolt_content_delete', $params, $absolute);
    }

    public function getDuplicateLink(Content $content, bool $absolute = false): ?string
    {
        if ($content->getId() === null || ! $this->security->getUser()) {
            return null;
        }

        return $this->generateLink('bolt_content_duplicate', ['id' => $content->getId()], $absolute);
    }

    public function getStatusLink(Content $content, bool $absolute = false): ?string
    {
        if ($content->getId() === null || ! $this->security->getUser()) {
            return null;
        }

        $params = [
            'id' => $content->getId(),
            'token' => (string) $this->csrfTokenManager->getToken('status'),
        ];

        return $this->generateLink('bolt_content_status', $params, $absolute);
    }

    private function generateLink(string $route, array $params, $canonical = false): string
    {
        $canonicalRoute = $this->canonical->getCanonicalRoute($route, $params);

        try {
            $link = $this->urlGenerator->generate(
                $canonicalRoute,
                $params,
                $canonical ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
            );
        } catch (InvalidParameterException $e) {
            $this->logger->notice('Could not create URL for route \'' . $route . '\'. Perhaps the ContentType was changed or removed. Try clearing the cache');
            $link = '';
        }

        return $link;
    }

    public function getTaxonomies(Content $content): Collection
    {
        $taxonomies = [];
        foreach ($content->getTaxonomies() as $taxonomy) {
            $link = $this->urlGenerator->generate('taxonomy', [
                'taxonomyslug' => $taxonomy->getType(),
                'slug' => $taxonomy->getSlug(),
            ]);
            $taxonomy->setLink($link);
            $taxonomies[$taxonomy->getType()][$taxonomy->getSlug()] = $taxonomy;
        }

        return new Collection($taxonomies);
    }

    public function getListTemplates(TemplateselectField $field): LaravelCollection
    {
        $definition = $field->getDefinition();
        $current = current($field->getValue());

        $finder = new Finder();
        $finder
            ->files()
            ->in($this->config->getPath('theme'))
            ->name($definition->get('filter', '*.twig'))
            ->path($definition->get('path'));

        $options = [];

        if ($definition->get('required') === false) {
            $options = [[
                'key' => '',
                'value' => '(choose a template)',
                'selected' => false,
            ]];
        }

        foreach ($finder as $file) {
            $options[] = [
                'key' => $file->getRelativePathname(),
                'value' => $file->getRelativePathname(),
            ];

            if ($current === $file->getRelativePathname()) {
                $current = false;
            }
        }

        if ($current !== false) {
            $options[] = [
                'key' => $current,
                'value' => $current . ' (file seems to be missing)',
            ];
        }

        return new LaravelCollection($options);
    }

    public function pager(Environment $twig, Pagerfanta $records, string $template = '@bolt/helpers/_pager_basic.html.twig', string $class = 'pagination', int $surround = 3)
    {
        $params = array_merge(
            $this->request->get('_route_params'),
            $this->request->query->all()
        );

        $context = [
            'records' => $records,
            'surround' => $surround,
            'class' => $class,
            'route' => $this->request->get('_route'),
            'routeParams' => $params,
        ];

        return $twig->render($template, $context);
    }

    public function selectOptions(SelectField $field): LaravelCollection
    {
        $values = $field->getDefinition()->get('values');

        if (is_iterable($values)) {
            return $this->selectOptionsArray($field);
        }

        return $this->selectOptionsContentType($field);
    }

    private function selectOptionsArray(SelectField $field): LaravelCollection
    {
        $values = $field->getDefinition()->get('values');
        $currentValues = $field->getValue();

        $options = [];

        // We need to add this as a 'dummy' option for when the user is allowed
        // not to pick an option. This is needed, because otherwise the `select`
        // would default to the one.
        if (! $field->getDefinition()->get('required', true)) {
            $options[] = [
                'key' => '',
                'value' => '',
                'selected' => false,
            ];
        }

        if (! is_iterable($values)) {
            return new LaravelCollection($options);
        }

        foreach ($values as $key => $value) {
            $options[] = [
                'key' => $key,
                'value' => $value,
                'selected' => in_array($key, $currentValues, true),
            ];
        }

        return new LaravelCollection($options);
    }

    private function selectOptionsContentType(SelectField $field): LaravelCollection
    {
        [ $contentTypeSlug, $format ] = explode('/', $field->getDefinition()->get('values'));

        $options = [];

        if (! $field->getDefinition()->get('required')) {
            $options[] = [
                'key' => '',
                'value' => '',
            ];
        }

        if (! empty($field->getDefinition()->get('limit'))) {
            $maxAmount = $field->getDefinition()->get('limit');
        } else {
            $maxAmount = $this->config->get('maximum_listing_select', 200);
        }
        $order = $field->getDefinition()->get('order', '');

        $params = [
            'limit' => $maxAmount,
            'order' => $order,
        ];

        /** @var Content[] $records */
        $records = iterator_to_array($this->query->getContent($contentTypeSlug, $params)->getCurrentPageResults());

        foreach ($records as $record) {
            $options[] = [
                'key' => $record->getId(),
                'value' => ContentHelper::get($record, $format),
            ];
        }

        return new LaravelCollection($options);
    }

    public function taxonomyOptions(LaravelCollection $taxonomy): LaravelCollection
    {
        $options = [];

        // We need to add this as a 'dummy' option for when the user is allowed
        // not to pick an option. This is needed, because otherwise the `select`
        // would default to the first option.
        if ($taxonomy['required'] === false) {
            $options[] = [
                'key' => '',
                'value' => '',
            ];
        }

        if ($taxonomy['behaves_like'] === 'tags') {
            $allTaxonomies = $this->taxonomyRepository->findBy(['type' => $taxonomy['slug']]);
            foreach ($allTaxonomies as $item) {
                $taxonomy['options'][$item->getSlug()] = $item->getName();
            }
        }

        foreach ($taxonomy['options'] as $key => $value) {
            $options[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return new LaravelCollection($options);
    }

    public function taxonomyValues(\Doctrine\Common\Collections\Collection $current, LaravelCollection $taxonomy): LaravelCollection
    {
        $values = [];

        foreach ($current as $value) {
            $values[$value->getType()][] = $value->getSlug();
        }

        if ($taxonomy['slug']) {
            $values = $values[$taxonomy['slug']] ?? [];
        }

        if (empty($values) && $taxonomy['required']) {
            $values[] = key($taxonomy['options']);
        }

        return new LaravelCollection($values);
    }

    public function icon(?Content $record = null, string $icon = 'question-circle'): string
    {
        if ($record instanceof Content) {
            $icon = $record->getIcon();
        }

        $icon = str_replace('fa-', '', $icon);

        return "<i class='fas mr-2 fa-${icon}'></i>";
    }

    public function hasPath(Content $record, string $path): bool
    {
        try {
            $result = $this->query
                ->getContent($path);

            if ($result instanceof Content) {
                return $record === $result;
            }

            $pager = $result
                ->setMaxPerPage(1)
                ->setCurrentPage(1);
            $content = iterator_to_array($pager->getCurrentPageResults())[0];

            return $record === $content;
        } catch (\Throwable $e) {
        }

        return false;
    }

    public function statusOptions(Content $record)
    {
        $options = [];

        foreach (Statuses::all() as $option) {
            $options[] = [
                'key' => $option,
                'value' => $this->translator->trans('status.' . $option),
                'selected' => $option === $record->getStatus(),
            ];
        }

        return $options;
    }

    public function getSpecialFeature(Content $record): string
    {
        if ($this->isHomepage($record)) {
            return 'homepage';
        }

        if ($this->is404($record)) {
            return '404';
        }

        if ($this->isMaintenance($record)) {
            return 'maintenance';
        }

        return '';
    }

    public function isHomepage(Content $content): bool
    {
        return $this->isSpecialpage($content, 'homepage');
    }

    public function is404(Content $content): bool
    {
        return $this->isSpecialpage($content, 'notfound');
    }

    public function isMaintenance(Content $content): bool
    {
        return $this->isSpecialpage($content, 'maintenance');
    }

    private function isSpecialpage(Content $content, string $type): bool
    {
        $configSetting = $this->config->get('general/' . $type);

        if (! is_iterable($configSetting)) {
            $configSetting = (array) $configSetting;
        }

        foreach ($configSetting as $item) {
            $item = explode('/', $item);

            // Discard candidate if contentTypes don't match
            if ($item[0] !== $content->getContentTypeSingularSlug() && $item[0] !== $content->getContentTypeSlug()) {
                continue;
            }

            $idOrSlug = $item[1] ?? null;

            // Success if we either have no id/slug for a Singleton, or if the id/slug matches
            if ((empty($idOrSlug) && $content->getDefinition()->get('singleton')) ||
                ($idOrSlug === $content->getSlug() || $idOrSlug === (string) $content->getId())) {
                return true;
            }
        }

        return false;
    }

    public function isHomepageListing(ContentType $contentType): bool
    {
        $homepageSetting = $this->config->get('general/homepage');

        if ($homepageSetting === $contentType->get('slug') || $homepageSetting === $contentType->get('singular_slug')) {
            return true;
        }

        return false;
    }
}
