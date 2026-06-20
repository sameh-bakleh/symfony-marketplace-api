<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\State\PublishedProductItemProvider;
use App\ApiPlatform\State\PublishedProductsProvider;
use App\Enum\ProductStatus;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: PublishedProductsProvider::class,
            security: 'is_granted("PUBLIC_ACCESS")',
            paginationEnabled: false,
        ),
        new Get(
            provider: PublishedProductItemProvider::class,
            security: 'is_granted("PUBLIC_ACCESS")',
        ),
    ],
    normalizationContext: ['groups' => ['product:read']],
)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'uniq_products_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_products_category_status', columns: ['category_id', 'status'])]
#[ORM\Index(name: 'idx_products_seller_status', columns: ['seller_id', 'status'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $seller = null;

    #[ORM\ManyToOne(inversedBy: 'products', targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    #[ORM\Column(length: 200)]
    #[Groups(['product:read'])]
    private string $title = '';

    #[ORM\Column(length: 200)]
    #[Groups(['product:read'])]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    #[Groups(['product:read'])]
    private string $description = '';

    #[ORM\Column]
    #[Groups(['product:read'])]
    private int $priceMinor = 0;

    #[ORM\Column(length: 3)]
    #[Groups(['product:read'])]
    private string $currency = 'USD';

    #[ORM\Column(enumType: ProductStatus::class)]
    #[Groups(['product:read'])]
    private ProductStatus $status = ProductStatus::Draft;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, WishlistItem> */
    #[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $wishlistItems;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->wishlistItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceMinor(): int
    {
        return $this->priceMinor;
    }

    public function setPriceMinor(int $priceMinor): self
    {
        $this->priceMinor = $priceMinor;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function setStatus(ProductStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
