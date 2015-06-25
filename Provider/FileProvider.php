<?php

namespace Symbio\OrangeGate\MediaBundle\Provider;

use Sonata\AdminBundle\Form\FormMapper;
use \Sonata\MediaBundle\Provider\FileProvider as BaseFileProvider;
use Sonata\MediaBundle\Model\MediaInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class FileProvider extends BaseFileProvider
{
	/**
	 * {@inheritdoc}
	 */
	public function generatePublicUrl(MediaInterface $media, $format)
	{
		if ($format == 'reference') {
			$path = $this->getReferenceImage($media);
			return $this->getCdn()->getPath($path, $media->getCdnIsFlushable());
		} else {
			return sprintf('/bundles/sonatamedia/files/%s/file.png', $format != 'admin' ? $format : '256');
		}
	}

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper)
    {
        $formMapper->add('name');
        $formMapper->add('enabled', null, array('required' => false));
        $formMapper->add('authorName');
        $formMapper->add('cdnIsFlushable');
        $formMapper->add('description');
        $formMapper->add('copyright');
        $formMapper->add('binaryContent', 'file', array('required' => false));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper)
    {
        $formMapper->add('name', null, array('required' => false));
        $formMapper->add('description');
        $formMapper->add('copyright');
        $formMapper->add('binaryContent', 'file', array(
            'constraints' => array(
                new NotBlank(),
                new NotNull()
            )
        ));
    }
}