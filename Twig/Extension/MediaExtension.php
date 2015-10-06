<?php

namespace Symbio\OrangeGate\MediaBundle\Twig\Extension;

use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\PathTokenParser;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\Pool;

class MediaExtension extends \Twig_Extension
{
    protected $resources = array();

    protected $mediaManager;
    protected $mediaService;

    protected $router;

    protected $environment;

    /**
     * @param Pool             $mediaService
     * @param ManagerInterface $mediaManager
     */
    public function __construct(Pool $mediaService, ManagerInterface $mediaManager, $router)
    {
        $this->mediaService = $mediaService;
        $this->mediaManager = $mediaManager;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(
            new PathTokenParser($this->getName()),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('og_image', array($this, 'imageFilter')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param mixed $media
     *
     * @return null|\Sonata\MediaBundle\Model\MediaInterface
     */
    private function getMedia($media)
    {
        if (!$media instanceof MediaInterface && strlen($media) > 0) {
            $media = $this->mediaManager->findOneBy(array(
                'id' => $media
            ));
        }

        if (!$media instanceof MediaInterface) {
            return false;
        }

        if ($media->getProviderStatus() !== MediaInterface::STATUS_OK) {
            return false;
        }

        return $media;
    }

    /**
     * @return \Sonata\MediaBundle\Provider\Pool
     */
    public function getMediaService()
    {
        return $this->mediaService;
    }

    /**
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param string                                   $format
     *
     * @return string
     */
    public function path($media = null, $format)
    {
        $media = $this->getMedia($media);

        if (!$media) {
             return '';
        }

        $provider = $this->getMediaService()
           ->getProvider($media->getProviderName());

        $format = $provider->getFormatName($media, $format);

        return $provider->generatePublicUrl($media, $format).'?'.$media->getUpdatedAt()->getTimestamp();
    }

    /**
     * Return block translation by key.
     *
     * @param string $content
     * @return string
     */
    public function imageFilter($content)
    {
        $temporaryImageUrl = substr($this->router->generate('orangegate_media_show', array('id' => 1)),0,-1);
        return preg_replace_callback(
            '|'.$temporaryImageUrl.'(\d+)(/(.+)([\'"]))?|',
            function ($matches) {
                return $this->path($matches[1], isset($matches[3]) && $matches[3] ? $matches[3] : 'reference').(isset($matches[4]) && $matches[4] ? $matches[4] : '');
            },
            $content
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orangegate_media';
    }
}
