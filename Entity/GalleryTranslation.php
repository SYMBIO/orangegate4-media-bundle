<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;

/**
 * Legacy gallery translation — unmapped until media__gallery_translation exists in target DB.
 */
class GalleryTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 8)]
    protected ?string $locale = null;

    #[ORM\ManyToOne(targetEntity: Gallery::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'object_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    protected ?Gallery $object = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $slug = null;

    public function __construct(?string $locale = null, ?string $name = null, ?string $description = null)
    {
        $this->locale = $locale;
        $this->name = $name;
        $this->description = $description;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setObject(?Gallery $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getObject(): ?Gallery
    {
        return $this->object;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        $slugify = new Slugify();
        $this->setSlug($slugify->slugify((string) $this->getName()));
    }
}
