<?php

namespace Symbio\OrangeGate\MediaBundle\Twig\Extension;

use Sonata\FormatterBundle\Extension\BaseProxyExtension;
use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\PathTokenParser;
use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\MediaTokenParser;
use Symbio\OrangeGate\MediaBundle\Twig\TokenParser\ThumbnailTokenParser;

class FormatterMediaExtension extends BaseProxyExtension
{
    protected $twigExtension;

    /**
     * @param \Twig_Extension $twigExtension
     */
    public function __construct(\Twig_Extension $twigExtension)
    {
        $this->twigExtension = $twigExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTags()
    {
        return array(
            'og_path',
            'og_media',
            'og_thumbnail',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods()
    {
        return array(
            'Sonata\MediaBundle\Model\MediaInterface' => array(
                'getproviderreference'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(
            new PathTokenParser($this->getName()),
            new MediaTokenParser($this->getName()),
            new ThumbnailTokenParser($this->getName()),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTwigExtension()
    {
        return $this->twigExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orangegate_formatter_media';
    }

    /**
     * @param integer $media
     * @param string  $format
     *
     * @return string
     */
    public function path($media = null, $format)
    {
        return $this->getTwigExtension()->path($media, $format);
    }
}
