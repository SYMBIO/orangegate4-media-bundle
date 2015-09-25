<?php

namespace Symbio\OrangeGate\MediaBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\MediaBundle\Provider\Pool;
use Symbio\OrangeGate\AdminBundle\Admin\Admin as BaseAdmin;

class GalleryAdmin extends BaseAdmin
{
    protected $pool;
    protected $translationDomain = 'SymbioOrangeGateMediaBundle';

    /**
     * @param string                            $code
     * @param string                            $class
     * @param string                            $baseControllerName
     * @param \Sonata\MediaBundle\Provider\Pool $pool
     */
    public function __construct($code, $class, $baseControllerName, Pool $pool)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->pool = $pool;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper
            ->with('General')
            ->add('enabled', null, array('required' => false))
            ->add('translations', 'orangegate_translations', array(
                'translation_domain' => $this->translationDomain,
                'label' => false,
                'fields' => array(
                    'name' => array(),
                    'description' => array(
                        'field_type' => 'ckeditor',
                        'config_name' => 'news'
                    ),
                ),
                'exclude_fields' => array('slug')
            ))
            ->end()
            ->with('Gallery')
            ->add('galleryHasMedias', 'orangegate_type_media_collection', array(
                'label' => 'Images',
                'required' => false), array(
                'link_parameters' => array('context' => 'gallery'),
                'media_type' => 'image',
                'sortable' => 'position'
            ))
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('enabled', 'boolean', array('editable' => true))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('enabled')
        ;
    }

    public function prePersist($gallery)
    {
        foreach ($gallery->getGalleryHasMedias() as $media) {
            $media->setGallery($gallery);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($gallery)
    {
        $this->prePersist($gallery);
    }
}