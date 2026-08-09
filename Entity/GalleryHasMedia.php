<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseGalleryItem;
use Sonata\MediaBundle\Model\GalleryInterface;
use Sonata\MediaBundle\Model\GalleryItemInterface;
use Sonata\MediaBundle\Model\MediaInterface;

/**
 * Sonata gallery item mapped to legacy-compatible table media__gallery_item.
 *
 * gallery/media associations are registered by SonataMediaExtension (DoctrineCollector).
 *
 * @phpstan-extends BaseGalleryItem
 */
#[ORM\Entity]
#[ORM\Table(name: 'media__gallery_item')]
class GalleryHasMedia extends BaseGalleryItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Gallery|null
     */
    public function getGallery(): ?GalleryInterface
    {
        $gallery = parent::getGallery();

        return $gallery instanceof Gallery ? $gallery : null;
    }

    public function setGallery(?GalleryInterface $gallery = null): void
    {
        parent::setGallery($gallery);
    }

    /**
     * @return Media|null
     */
    public function getMedia(): ?MediaInterface
    {
        $media = parent::getMedia();

        return $media instanceof Media ? $media : null;
    }

    public function setMedia(?MediaInterface $media = null): void
    {
        parent::setMedia($media);
    }
}
