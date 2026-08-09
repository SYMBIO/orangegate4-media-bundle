<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseMedia;
use Symbio\OrangeGate\ClassificationBundle\Entity\Category;

#[ORM\Entity]
#[ORM\Table(name: 'media__media')]
class Media extends BaseMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'lang', type: 'string', length: 20, nullable: true)]
    protected ?string $lang = null;

    /**
     * @var Collection<int, GalleryHasMedia>
     */
    protected Collection $galleryHasMedias;

    // category association is registered by SonataMediaExtension (DoctrineCollector)

    public function __construct()
    {
        parent::__construct();
        $this->galleryHasMedias = new ArrayCollection();
        $this->enabled = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addGalleryHasMedia(GalleryHasMedia $galleryHasMedia): self
    {
        if (!$this->galleryHasMedias->contains($galleryHasMedia)) {
            $this->galleryHasMedias->add($galleryHasMedia);
        }

        return $this;
    }

    public function removeGalleryHasMedia(GalleryHasMedia $galleryHasMedia): void
    {
        $this->galleryHasMedias->removeElement($galleryHasMedia);
    }

    /**
     * @return Collection<int, GalleryHasMedia>
     */
    public function getGalleryHasMedias(): Collection
    {
        return $this->galleryHasMedias;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }
}
