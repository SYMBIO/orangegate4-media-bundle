<?php

namespace Symbio\OrangeGate\MediaBundle\Provider;

use Cocur\Slugify\Slugify;
use Gaufrette\Filesystem;
use Sonata\MediaBundle\CDN\CDNInterface;
use Sonata\MediaBundle\Generator\GeneratorInterface;
use Sonata\MediaBundle\Thumbnail\ThumbnailInterface;
use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\AdminBundle\Form\FormMapper;
use \Sonata\MediaBundle\Provider\FileProvider as BaseFileProvider;
use Sonata\MediaBundle\Model\MediaInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class FileProvider extends BaseFileProvider
{
    protected $allowedExtensions;

    protected $allowedMimeTypes;

    public function __construct($name, Filesystem $filesystem, CDNInterface $cdn, GeneratorInterface $pathGenerator, ThumbnailInterface $thumbnail, array $allowedExtensions = array(), array $allowedMimeTypes = array(), MetadataBuilderInterface $metadata = null)
    {
        parent::__construct($name, $filesystem, $cdn, $pathGenerator, $thumbnail,$allowedExtensions, $allowedMimeTypes, $metadata);
        $this->allowedMimeTypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $this->allowedMimeTypes[] = 'image/jpeg';
        $this->allowedMimeTypes[] = 'image/png';
        $this->allowedMimeTypes[] = 'image/gif';
        $this->allowedMimeTypes[] = 'image/bmp';
        $this->allowedExtensions[] = 'jpeg';
        $this->allowedExtensions[] = 'jpg';
        $this->allowedExtensions[] = 'png';
        $this->allowedExtensions[] = 'gif';
        $this->allowedExtensions[] = 'bmp';
    }

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
    protected function generateReferenceName(MediaInterface $media)
    {
        $filename = $media->getMetadataValue('filename');
        $extension = substr($filename,strrpos($filename,'.')+1);
        $filenameSlug = (new Slugify())->slugify(substr($filename,0,strlen($filename)-strlen($extension)));
        return uniqid().'_'.$filenameSlug.'.'.$extension;
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