<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symbio\OrangeGate\MediaBundle\Admin\MediaAdmin;
use Symbio\OrangeGate\PageBundle\Entity\Page;
use Symbio\OrangeGate\PageBundle\Entity\SitePool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * OrangeGate media admin — mosaic list, browser, upload (Sonata 4 / Symfony 7 port).
 *
 * @phpstan-extends CRUDController<MediaInterface>
 */
class MediaAdminController extends CRUDController
{
    public static function getSubscribedServices(): array
    {
        return [
            'orangegate.site.pool' => SitePool::class,
            'sonata.classification.manager.category' => CategoryManagerInterface::class,
            'doctrine.orm.entity_manager' => EntityManagerInterface::class,
        ] + parent::getSubscribedServices();
    }

    public function browserAction(Request $request): Response
    {
        $admin = $this->assertMediaAdmin();

        if (false === $admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $linkTo = $request->query->get('linkTo', 'page');
        $tplParams = [
            'action' => 'browser',
            'base_template' => '@SymbioOrangeGateMedia/layout.html.twig',
            'linkTo' => $linkTo,
        ];

        if ('page' === $linkTo) {
            $pool = $this->container->get('orangegate.site.pool');
            \assert($pool instanceof SitePool);

            $currentSite = $pool->getCurrentSite($request);
            $pageList = $this->loadPageList($currentSite, (string) $request->getLocale());

            return $this->render('@SymbioOrangeGateMedia/MediaAdmin/pages.html.twig', array_merge($tplParams, [
                'pages' => $pageList,
                'currentSite' => $currentSite,
                'sites' => $pool->getSites(),
            ]));
        }

        $categoryManager = $this->container->get('sonata.classification.manager.category');
        \assert($categoryManager instanceof CategoryManagerInterface);

        $currentContext = $admin->getPersistentParameter('context');
        $currentCategory = $admin->getPersistentParameter('category');
        $rootCategory = $categoryManager->getRootCategory($currentContext);

        $contextInCategory = $categoryManager->findBy([
            'id' => (int) $request->query->get('category'),
            'context' => $currentContext,
        ]);

        $datagrid = $admin->getDatagrid();
        $datagrid->setValue('context', null, $currentContext);
        $datagrid->setValue('providerName', null, $admin->getPersistentParameter('provider'));

        if (!$currentCategory || [] === $contextInCategory) {
            $currentCategory = $rootCategory;
        }

        $datagrid->setValue('category', null, $currentCategory);

        $contextList = [];
        foreach ($admin->getContextList() as $context) {
            $contextList[$context->getId()] = $context->getName();
        }

        $formats = [];
        $mediaPool = $this->container->get('sonata.media.pool');
        \assert($mediaPool instanceof Pool);

        foreach ($datagrid->getResults() as $media) {
            \assert($media instanceof MediaInterface);
            $formats[$media->getId()] = $mediaPool->getFormatNamesByContext($media->getContext());
        }

        $formView = $datagrid->getForm()->createView();
        $this->setFormTheme($formView, $admin->getFilterTheme());

        return $this->render('@SymbioOrangeGateMedia/MediaAdmin/browser.html.twig', array_merge($tplParams, [
            'form' => $formView,
            'datagrid' => $datagrid,
            'formats' => $formats,
            'contextList' => $contextList,
            'rootCategory' => $rootCategory,
            'currentCategory' => $categoryManager->find($currentCategory),
        ]));
    }

    public function listAction(Request $request): Response
    {
        $admin = $this->assertMediaAdmin();

        if (false === $admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $listMode = $request->query->get('_list_mode', 'mosaic');
        \assert(\is_string($listMode));
        $admin->setListMode($listMode);

        $sitesPool = $this->container->get('orangegate.site.pool');
        \assert($sitesPool instanceof SitePool);

        $sites = $sitesPool->getSites();
        $currentSite = $sitesPool->getCurrentSite($request);

        $datagrid = $admin->getDatagrid();
        $filters = $request->query->all('filter');

        if (!\array_key_exists('context', $filters)) {
            $context = $admin->getPersistentParameter('context');
        } else {
            $context = $filters['context']['value'];
        }

        $datagrid->setValue('context', null, $context);

        $categoryManager = $this->container->get('sonata.classification.manager.category');
        \assert($categoryManager instanceof CategoryManagerInterface);

        $category = $categoryManager->getRootCategory($context);

        if ([] === $filters) {
            $datagrid->setValue('category', null, $category->getId());
        }

        if ($request->query->has('category')) {
            $contextInCategory = $categoryManager->findBy([
                'id' => (int) $request->query->get('category'),
                'context' => $context,
            ]);

            if ([] !== $contextInCategory) {
                $datagrid->setValue('category', null, $request->query->get('category'));
            } else {
                $datagrid->setValue('category', null, $category->getId());
            }
        }

        if ($request->query->has('provider')) {
            $datagrid->setValue('providerName', null, $request->query->get('provider'));
        }

        $formView = $datagrid->getForm()->createView();
        $this->setFormTheme($formView, $admin->getFilterTheme());

        return $this->render($admin->getTemplateRegistry()->getTemplate('list'), [
            'action' => 'list',
            'form' => $formView,
            'datagrid' => $datagrid,
            'root_category' => $category,
            'sites' => $sites,
            'currentSite' => $currentSite,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ]);
    }

    public function uploadAction(Request $request): Response
    {
        $admin = $this->assertMediaAdmin();

        if (false === $admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $providerName = $request->query->get('provider');
        $file = $request->files->get('upload');

        if (!$request->isMethod('POST') || !\is_string($providerName) || null === $file) {
            throw new NotFoundHttpException();
        }

        $mediaPool = $this->container->get('sonata.media.pool');
        \assert($mediaPool instanceof Pool);

        $mediaManager = $this->container->get('sonata.media.manager.media');
        \assert($mediaManager instanceof MediaManagerInterface);

        $context = $request->query->get('context', $mediaPool->getDefaultContext());
        \assert(\is_string($context));

        $media = $mediaManager->create();
        $media->setContext($context);
        $media->setProviderName($providerName);
        $media->setBinaryContent($file);

        $provider = $mediaPool->getProvider($providerName);
        $provider->transform($media);
        $mediaManager->save($media);

        return $this->render('@SymbioOrangeGateMedia/MediaAdmin/upload.html.twig', [
            'action' => 'list',
            'object' => $media,
        ]);
    }

    public function createAction(Request $request): Response
    {
        if ($request->isMethod('GET') && null === $request->query->get('provider')) {
            $mediaPool = $this->container->get('sonata.media.pool');
            \assert($mediaPool instanceof Pool);
            $context = $request->query->get('context', $mediaPool->getDefaultContext());
            \assert(\is_string($context));

            return $this->render('@SonataMedia/MediaAdmin/select_provider.html.twig', [
                'providers' => $mediaPool->getProvidersByContext($context),
                'action' => 'create',
            ]);
        }

        if ($request->isMethod('POST') && $request->query->has('provider')) {
            $request->query->remove('provider');
        }

        return parent::createAction($request);
    }

    /**
     * @return array<int, Page>
     */
    protected function loadPageList(?object $site, string $locale): array
    {
        if (null === $site) {
            return [];
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        $list = $entityManager->createQuery(
            'SELECT p
             FROM '.Page::class.' p
             INNER JOIN p.translations t
             WHERE t.enabled = :enabled
               AND p.site = :site
               AND p.parent IS NULL
               AND t.locale = :locale
               AND p.routeName NOT LIKE :internalRoute
               AND t.url NOT LIKE :dynamicUrl
             ORDER BY p.position ASC'
        )
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('enabled', true)
            ->setParameter('internalRoute', '_page_internal_%')
            ->setParameter('dynamicUrl', '%{%')
            ->getResult();

        $pages = [];
        foreach ($list as $page) {
            \assert($page instanceof Page);
            $this->childWalker($page, $pages);
        }

        return $pages;
    }

    /**
     * @param array<int, Page> $choices
     */
    private function childWalker(Page $page, array &$choices): void
    {
        if (
            !$page->isInternal()
            && !str_contains((string) $page->getUrl(), '{')
        ) {
            $parent = $page->getParent();
            if ($parent instanceof Page && $parent->getParent()) {
                $page->setName($parent->getName().'/'.$page->getName());
            }

            $choices[(int) $page->getId()] = $page;

            foreach ($page->getChildren() as $child) {
                if ($child instanceof Page) {
                    $this->childWalker($child, $choices);
                }
            }
        }
    }

    private function assertMediaAdmin(): MediaAdmin
    {
        \assert($this->admin instanceof MediaAdmin);

        return $this->admin;
    }
}
