<?php

namespace Symbio\OrangeGate\MediaBundle\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;

class MediaAdmin extends \Sonata\MediaBundle\Admin\ORM\MediaAdmin
{
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'name',
        '_sort_order' => 'asc'
    );

    protected $listModes = array(
//        'list' => array(
//            'class' => 'fa fa-list fa-fw',
//        ),
        'mosaic' => array(
            'class' => 'fa fa-th-large fa-fw',
        ),
//        'tree' => array(
//            'class' => 'fa fa-sitemap fa-fw',
//        ),
    );

    /**
     * @var ContextManagerInterface
     */
    protected $contextManager;

    /**
     * @var SitePool
     */
    protected $sitePool;

    /**
     * {@inheritdoc}
     */
    public function __construct($code, $class, $baseControllerName, Pool $pool, CategoryManagerInterface $categoryManager, ContextManagerInterface $contextManager, SitePool $sitePool)
    {
        parent::__construct($code, $class, $baseControllerName, $pool, $categoryManager);

        $this->contextManager = $contextManager;
        $this->sitePool = $sitePool;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->add('browser', 'browser');
        $collection->add('upload', 'upload');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, array('list'))) {
            return;
        }

        foreach ($this->getContextList() as $context) {
            $menu->addChild(
                $this->trans($context->getName()),
                array('uri' => $this->generateUrl('list', array('context' => $context->getId(), 'category' => null, 'hide_context' => null)))
            );
        }
    }

    /**
     * Returns list of available contexts
     *
     * @return array
     */
    public function getContextList()
    {
        $criteria = array(
            'site' => $this->sitePool->getCurrentSite($this->getRequest())
        );

        return $this->contextManager->findBy($criteria, array('name' => 'asc'));
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistentParameters()
    {
        $parameters = parent::getPersistentParameters();

        if (!$this->hasRequest()) {
            return $parameters;
        }

        if ($filter = $this->getRequest()->get('filter') && isset($filter['context'])) {
            $context = $filter['context']['value'];
        } else {
            $context = $this->getRequest()->get('context', $this->pool->getDefaultContext());
        }

        $providers = $this->pool->getProvidersByContext($context);
        $provider = $this->getRequest()->get('provider');

        // if the context has only one provider, set it into the request
        // so the intermediate provider selection is skipped
        if (count($providers) == 1 && null === $provider) {
            $provider = array_shift($providers)->getName();
            $this->getRequest()->query->set('provider', $provider);
        }

        $categoryId = $this->getRequest()->get('category');

        if (!$categoryId) {
            $categoryId = $this->categoryManager->getRootCategory($context)->getId();
        }

        return array_merge($parameters, array(
            'provider' => $provider,
            'context' => $context,
            'category' => $categoryId,
            'hide_context' => (bool)$this->getRequest()->get('hide_context')
        ));
    }

    /**
     * Set datagrid values used before datagrid build
     *
     * @param $values array
     * @return AdminInterface
     */
    public function setDatagridValues(array $values)
    {
        $this->datagridValues = array_merge($this->datagridValues, $values);

        return $this;
    }
}
