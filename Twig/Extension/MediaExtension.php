<?php

namespace Symbio\OrangeGate\MediaBundle\Twig\Extension;

use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\PathTokenParser;
use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\ThumbnailTokenParser;
use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\MediaTokenParser;
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
        return array (
            new MediaTokenParser($this->getName()),
            new ThumbnailTokenParser($this->getName()),
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
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param string                                   $format
     * @param array                                    $options
     *
     * @return string
     */
    public function media($media = null, $format, $options = array())
    {
        $media = $this->getMedia($media);

        if (!$media) {
            return '';
        }

        $provider = $this
            ->getMediaService()
            ->getProvider($media->getProviderName());

        $format = $provider->getFormatName($media, $format);

        $options = $provider->getHelperProperties($media, $format, $options);

        if (array_key_exists('src', $options)) {
            $options['src'] .= '?v=' . $media->getUpdatedAt()->getTimestamp();
        }

        return $this->render($provider->getTemplate('helper_view'), array(
            'media'    => $media,
            'format'   => $format,
            'options'  => $options,
        ));
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
     * Returns the thumbnail for the provided media
     *
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param string                                   $format
     * @param array                                    $options
     *
     * @return string
     */
    public function thumbnail($media = null, $format, $options = array())
    {
        $media = $this->getMedia($media);

        if (!$media) {
            return '';
        }

        $provider = $this->getMediaService()
           ->getProvider($media->getProviderName());

        $format = $provider->getFormatName($media, $format);
        $format_definition = $provider->getFormat($format);

        // build option
        $defaultOptions = array(
            'title' => $media->getName(),
        );

        if ($format_definition['width']) {
            $defaultOptions['width'] = $format_definition['width'];
        }
        if ($format_definition['height']) {
            $defaultOptions['height'] = $format_definition['height'];
        }

        $options = array_merge($defaultOptions, $options);

        $options['src'] = $this->generatePublicUrl($provider, $media, $format);

        return $this->render($provider->getTemplate('helper_thumbnail'), array(
            'media'    => $media,
            'options'  => $options,
        ));
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return mixed
     */
    public function render($template, array $parameters = array())
    {
        if (!isset($this->resources[$template])) {
            $this->resources[$template] = $this->environment->loadTemplate($template);
        }

        return $this->resources[$template]->render($parameters);
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

        return $this->generatePublicUrl($provider, $media, $format);
    }

    /**
     * Return block translation by key.
     *
     * @param string $content
     * @return string
     */
    public function imageFilter($content)
    {
        $temporaryImageUrl = str_replace('/app_dev.php', '', substr($this->router->generate('orangegate_media_show', array('id' => 1)), 0, -1));
        $temporaruDonwloadUrl = str_replace('/app_dev.php', '', substr($this->router->generate('sonata_media_download', array('id' => 1)), 0, -1));

        return preg_replace(
            '|' . $temporaryImageUrl . '(\d+)(/(.+)([\'"]))?|',
            $temporaruDonwloadUrl . '$1',
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

    private function generatePublicUrl($provider, $media, $format)
    {
        return $provider->generatePublicUrl($media, $format) . '?v=' . $media->getUpdatedAt()->getTimestamp();
    }
}
