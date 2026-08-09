<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseGallery;
use Sonata\MediaBundle\Model\GalleryItemInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * OrangeGate gallery entity — Sonata GalleryInterface via BaseGallery.
 *
 * galleryItems association is registered by SonataMediaExtension (DoctrineCollector).
 *
 * OG extensions (site, translations, slug) remain unmapped until legacy columns/tables
 * exist in the target database (see docs/spike/gallery-alignment-check.md).
 *
 * @phpstan-extends BaseGallery<GalleryHasMedia>
 */
#[ORM\Entity]
#[ORM\Table(name: 'media__gallery')]
class Gallery extends BaseGallery
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /** Unmapped until media__gallery.site_id exists in target DB */
    private ?object $site = null;

    /** Unmapped — legacy column / translation fallback */
    private ?string $description = null;

    /** Unmapped until media__gallery.slug exists in target DB */
    private ?string $slug = null;

    /**
     * Unmapped until media__gallery_translation exists in target DB.
     *
     * @var Collection<int, GalleryTranslation>
     */
    #[Assert\Valid]
    private Collection $translations;

    public function __construct()
    {
        parent::__construct();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getSite(): ?object
    {
        return $this->site;
    }

    public function setSite(?object $site): self
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return Collection<int, GalleryHasMedia>
     */
    public function getGalleryHasMedias(): Collection
    {
        /** @var Collection<int, GalleryHasMedia> $items */
        $items = $this->getGalleryItems();

        return $items;
    }

    public function addGalleryHasMedia(GalleryHasMedia $galleryHasMedia): self
    {
        $this->addGalleryItem($galleryHasMedia);

        return $this;
    }

    public function removeGalleryHasMedia(GalleryHasMedia $galleryHasMedia): void
    {
        $this->removeGalleryItem($galleryHasMedia);
    }

    /**
     * @param Collection<int, GalleryHasMedia> $galleryHasMedias
     */
    public function setGalleryHasMedias(Collection $galleryHasMedias): self
    {
        $this->setGalleryItems($galleryHasMedias);

        return $this;
    }

    /**
     * @return Collection<int, GalleryHasMedia>
     */
    public function getGalleryItems(): Collection
    {
        /** @var Collection<int, GalleryHasMedia> $items */
        $items = parent::getGalleryItems();

        return $items;
    }

    public function addGalleryItem(GalleryItemInterface $galleryItem): void
    {
        parent::addGalleryItem($galleryItem);
    }

    /**
     * @return Collection<int, GalleryTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(GalleryTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            if ($translation->getName()) {
                $this->translations->add($translation);
                $translation->setObject($this);
            }
        }

        return $this;
    }

    public function removeTranslation(GalleryTranslation $translation): self
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
        }

        return $this;
    }

    /** Fluent helper — BaseGallery::setName() is void (GalleryInterface). */
    public function withName(?string $name): self
    {
        $this->setName($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function withSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /** Fluent helper — BaseGallery::setEnabled() is void (GalleryInterface). */
    public function withEnabled(bool $enabled): self
    {
        $this->setEnabled($enabled);

        return $this;
    }

    public function withUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->setUpdatedAt($updatedAt);

        return $this;
    }

    public function withCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->setCreatedAt($createdAt);

        return $this;
    }

    public function generateSlug(): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify((string) $this->getName());
    }

    #[Assert\Callback]
    public function isValid(ExecutionContextInterface $context): void
    {
        $valid = false;
        foreach ($this->translations as $trans) {
            if ($trans->getName()) {
                $valid = true;
                break;
            }
        }

        if (!$valid && !$this->getName()) {
            $context->buildViolation('Musíte vyplnit alespoň jednu jazykovou verzi.')
                ->atPath('translations')
                ->addViolation();
        }
    }
}
