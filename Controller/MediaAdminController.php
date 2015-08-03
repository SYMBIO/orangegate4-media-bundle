<?php

namespace Symbio\OrangeGate\MediaBundle\Controller;

use Sonata\MediaBundle\Controller\MediaAdminController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MediaAdminController extends Controller
{
    /**
     * Returns the response object associated with the browser action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws AccessDeniedException
     */
    public function browserAction()
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $linkTo = $this->getRequest()->query->get('linkTo', 'page');

        // params for both templates
        $tplName = null;
        $tplParams = array(
            'action' => 'browser',
            'base_template' => 'SymbioOrangeGateMediaBundle::layout.html.twig',
            'linkTo' => $linkTo,
        );

        // page link
        if ($linkTo == 'page') {
            $pageList =  $this->loadPageList();

            // set template values
            $tplName = 'SymbioOrangeGateMediaBundle:MediaAdmin:pages.html.twig';
            $tplParams['pages'] = $pageList;
        }

        // media file link
        else {
            $datagrid = $this->admin->getDatagrid();
            $datagrid->setValue('context', null, $this->admin->getPersistentParameter('context'));
            $datagrid->setValue('providerName', null, $this->admin->getPersistentParameter('provider'));

            // transform context list to associative array
            $contextList = array();
            foreach ($this->admin->getContextList() as $context) {
                $contextList[$context->getId()] = $context->getName();
            }

            // Store formats
            $formats = array();
            foreach ($datagrid->getResults() as $media) {
                $formats[$media->getId()] = $this->get('sonata.media.pool')->getFormatNamesByContext($media->getContext());
            }

            $formView = $datagrid->getForm()->createView();

            // set the theme for the current Admin Form
            $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

            // set template values
            $tplName = 'SymbioOrangeGateMediaBundle:MediaAdmin:browser.html.twig';
            $tplParams = array_merge($tplParams, array(
                'form' => $formView,
                'datagrid' => $datagrid,
                'formats' => $formats,
                'contextList' => $contextList,
            ));
        }

        // render template
        return $this->render($tplName, $tplParams);
    }


    /**
     * {@inheritdoc}
     */
    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        if ($listMode = $request->get('_list_mode', 'mosaic')) {
            $this->admin->setListMode($listMode);
        }

        $sitesPool = $this->get('orangegate.site.pool');
        $sites = $sitesPool->getSites();
        $currentSite = $sitesPool->getCurrentSite($request, $sites);

        $datagrid = $this->admin->getDatagrid();

        $filters = $request->get('filter');

        // set the default context
        if (!$filters || !array_key_exists('context', $filters)) {
            $context = $this->admin->getPersistentParameter('context',  $this->get('sonata.classification.manager.context')->findBy(array('site' => $currentSite)));
        } else {
            $context = $filters['context']['value'];
        }

        $datagrid->setValue('context', null, $context);

        // retrieve the main category for the tree view
        $category = $this->container->get('sonata.classification.manager.category')->getRootCategory($context);

        if (!$filters) {
            $datagrid->setValue('category', null, $category->getId());
        }

        if ($request->get('category')) {
            $contextInCategory = $this->container->get('sonata.classification.manager.category')->findBy(array(
                'id'      => (int) $request->get('category'),
                'context' => $context
            ));

            if (!empty($contextInCategory)) {
                $datagrid->setValue('category', null, $request->get('category'));
            } else {
                $datagrid->setValue('category', null, $category->getId());
            }
        }

        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'        => 'list',
            'form'          => $formView,
            'datagrid'      => $datagrid,
            'root_category' => $category,
            'sites'         => $sites,
            'currentSite'   => $currentSite,
            'csrf_token'    => $this->getCsrfToken('sonata.batch'),
        ));
    }

    /**
     * Loads lists of pages available that user can links to
     * @return array
     */
    protected function loadPageList() {
        $list = $this->get('doctrine')->getManager()->createQuery("
			SELECT
				p
			FROM
				SymbioOrangeGatePageBundle:Page p
				INNER JOIN p.translations t
			WHERE
				    t.enabled = :enabled
				AND p.parent IS NULL
				AND t.locale = :locale
				AND p.routeName NOT LIKE '_page_internal_%'
				AND t.url NOT LIKE '%{%'
		  	ORDER BY
		  		p.position
		")
            ->setParameter('locale', $this->getRequest()->getLocale())
            ->setParameter('enabled', true)
            ->getResult()
        ;

        $pages = array();
        foreach ($list as $page) {
            $this->childWalker($page, $pages);
        }
        return $pages;
    }


    /**
     * Builds list page from tree
     * @param $page
     * @param $choices
     */
    private function childWalker($page, &$choices)
    {
        if (
            !$page->isInternal()
            && strpos($page->getUrl(), '{') === FALSE
        ) {
            $parent = $page->getParent();
            if ($parent && $parent->getParent()) {
                $page->setName($parent->getName() . '/' . $page->getName());
            }

            $choices[$page->getId()] = $page;

            foreach ($page->getChildren() as $child) {
                $this->childWalker($child, $choices);
            }
        }
    }
}
