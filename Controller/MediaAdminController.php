<?php

namespace Symbio\OrangeGate\MediaBundle\Controller;

use Symbio\OrangeGate\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MediaAdminController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function createAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        if (!$request->get('provider') && $request->isMethod('get')) {
            return $this->render('SonataMediaBundle:MediaAdmin:select_provider.html.twig', array(
                'providers'     => $this->get('sonata.media.pool')->getProvidersByContext($request->get('context', $this->get('sonata.media.pool')->getDefaultContext())),
                'base_template' => $this->getBaseTemplate(),
                'admin'         => $this->admin,
                'action'        => 'create'
            ));
        }

        return parent::createAction();
    }

    /**
     * {@inheritdoc}
     */
    public function render($view, array $parameters = array(), Response $response = null, Request $request = null)
    {
        $parameters['media_pool']            = $this->container->get('sonata.media.pool');
        $parameters['persistent_parameters'] = $this->admin->getPersistentParameters();

        return parent::render($view, $parameters, $response, $request);
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
}
