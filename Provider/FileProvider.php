<?php

namespace Symbio\OrangeGate\MediaBundle\Provider;

use \Sonata\MediaBundle\Provider\FileProvider as BaseFileProvider;
use Sonata\MediaBundle\Model\MediaInterface;

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
}