<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Admin;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\MediaBundle\Admin\BaseMediaAdmin;
use Sonata\MediaBundle\Provider\Pool;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MediaAdmin extends BaseMediaAdmin
{
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_page' => 1,
        '_sort_by' => 'name',
        '_sort_order' => 'asc',
    ];

    /** @var array<string, array{class: string}> */
    protected array $listModes = [
        'mosaic' => [
            'class' => 'fa fa-th-large fa-fw',
        ],
    ];

    private SitePool $sitePool;

    public function __construct(
        Pool $pool,
        CategoryManagerInterface $categoryManager,
        ContextManagerInterface $contextManager,
        SitePool $sitePool,
    ) {
        parent::__construct($pool, $categoryManager, $contextManager);
        $this->sitePool = $sitePool;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('browser', 'browser');
        $collection->add('upload', 'upload');
    }

    protected function configureSideMenu(MenuItemInterface $menu, $action, ?AdminInterface $childAdmin = null): void
    {
        if (!$childAdmin && !\in_array($action, ['list'], true)) {
            return;
        }

        $currentContext = $this->getPersistentParameter('context');
        $contexts = $this->getContextList();

        if (\count($contexts) > 1) {
            foreach ($contexts as $context) {
                $child = $menu->addChild(
                    $this->trans($context->getName()),
                    ['uri' => $this->generateUrl('list', ['context' => $context->getId(), 'category' => null, 'hide_context' => null])]
                );

                if ($currentContext === $context->getId()) {
                    $child->setCurrent(true);
                }
            }
        }
    }

    /**
     * @return array<int, object>
     */
    public function getContextList(): array
    {
        return $this->contextManager->findBy(
            ['site' => $this->sitePool->getCurrentSite($this->getRequest())],
            ['name' => 'asc']
        );
    }

    protected function configurePersistentParameters(): array
    {
        if (!$this->hasRequest()) {
            return [];
        }

        $request = $this->getRequest();
        $filter = $request->query->all('filter');

        if (\array_key_exists('context', $filter)) {
            $context = $filter['context']['value'];
        } else {
            $context = $request->query->get('context', false);
            $availableContexts = array_map(static fn ($c) => $c->getId(), $this->getContextList());
            if (!$context || !\in_array($context, $availableContexts, true)) {
                $context = $availableContexts[0] ?? $this->pool->getDefaultContext();
            }
        }

        \assert(\is_string($context));

        $providers = $this->pool->getProvidersByContext($context);
        $provider = $request->query->get('provider');

        if (1 === \count($providers) && null === $provider) {
            $provider = array_shift($providers)->getName();
            $request->query->set('provider', $provider);
        }

        $parameters = [];
        if (1 < \count($providers) && null !== $provider) {
            $parameters['provider'] = $provider;
        }

        $categoryId = $request->query->get('category');
        if (null !== $this->categoryManager && null !== $this->contextManager && null === $categoryId) {
            $rootCategories = $this->categoryManager->getRootCategoriesForContext(
                $this->contextManager->find($context)
            );
            $rootCategory = current($rootCategories);
            if (false !== $rootCategory) {
                $categoryId = $rootCategory->getId();
            }
        }

        return array_merge($parameters, [
            'context' => $context,
            'category' => $categoryId,
            'hide_context' => $request->query->getBoolean('hide_context'),
        ]);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setDatagridValues(array $values): self
    {
        $this->datagridValues = array_merge($this->datagridValues, $values);

        return $this;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name')
            ->add('description')
            ->add('enabled')
            ->add('size')
            ->add('createdAt');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $options = ['choices' => []];

        foreach ($this->pool->getContexts() as $name => $context) {
            $options['choices'][$name] = $name;
        }

        $filter
            ->add('name')
            ->add('providerReference')
            ->add('enabled')
            ->add('context', null, [
                'field_type' => ChoiceType::class,
                'field_options' => $options,
                'show_filter' => true !== $this->getPersistentParameter('hide_context'),
            ]);

        if (null !== $this->categoryManager) {
            $filter->add('category', null, ['show_filter' => false]);
        }

        $filter
            ->add('width')
            ->add('height')
            ->add('contentType');

        $providersChoices = [];
        $providers = $this->pool->getProvidersByContext($this->getPersistentParameter('context', $this->pool->getDefaultContext()));
        foreach ($providers as $provider) {
            $name = $provider->getName();
            $providersChoices[$this->getTranslator()->trans(
                $name,
                [],
                $provider->getProviderMetadata()->getDomain() ?? $this->getTranslationDomain()
            )] = $name;
        }

        $filter->add('providerName', ChoiceFilter::class, [
            'field_options' => [
                'choices' => $providersChoices,
                'required' => false,
                'multiple' => false,
                'expanded' => false,
            ],
            'field_type' => ChoiceType::class,
        ]);
    }
}
