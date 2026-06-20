<?php

declare(strict_types=1);

namespace App\Service\Catalog;

use App\ApiResource\ProductResource;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\User;
use App\Enum\ProductStatus;
use App\Repository\ProductRepository;
use App\Service\Cache\ListingCacheKeys;
use App\Service\Cache\ListingCacheInvalidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ProductService
{
    private const UPLOAD_DIR = 'uploads/products';

    public function __construct(
        private ProductRepository $products,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private ListingCacheInvalidator $cacheInvalidator,
        private TagAwareCacheInterface $tagAwareCache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @param array{page?:int,perPage?:int,categoryId?:int|null,sellerId?:int|null,search?:string|null,minPrice?:int|null,maxPrice?:int|null,sort?:string|null} $query
     *
     * @return array{items: list<array<string,mixed>>, total: int, page: int, perPage: int}
     */
    public function listPublished(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['perPage'] ?? 20)));
        $filters = array_merge($query, ['page' => $page, 'perPage' => $perPage]);
        $key = ListingCacheKeys::productsList($filters);

        return $this->tagAwareCache->get($key, function (ItemInterface $item) use ($filters, $page, $perPage) {
            $item->tag(['product_listings']);
            $item->expiresAfter(120);
            $qb = $this->products->createPublishedListingQueryBuilder();
            $this->applyFilters($qb, $filters);
            $this->applySort($qb, (string) ($filters['sort'] ?? 'newest'));
            $countQb = clone $qb;
            $total = (int) $countQb->select('COUNT(DISTINCT p.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
            $items = $qb
                ->setFirstResult(($page - 1) * $perPage)
                ->setMaxResults($perPage)
                ->getQuery()
                ->getResult();
            /** @var list<Product> $items */

            return [
                'items' => ProductResource::collection($items, false),
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ];
        });
    }

    /**
     * @param list<UploadedFile> $uploadedImages
     */
    public function create(
        User $seller,
        Category $category,
        string $title,
        string $description,
        int $priceMinor,
        string $currency,
        ProductStatus $status,
        array $uploadedImages,
    ): Product {
        $base = $this->slugger->slug($title)->lower()->toString();
        if ($base === '') {
            $base = 'product';
        }
        $slug = $this->uniqueSlug($base);
        $p = (new Product())
            ->setSeller($seller)
            ->setCategory($category)
            ->setTitle($title)
            ->setSlug($slug)
            ->setDescription($description)
            ->setPriceMinor($priceMinor)
            ->setCurrency(strtoupper(substr($currency, 0, 3)))
            ->setStatus($status);
        $this->em->persist($p);
        $this->em->flush();
        $this->attachImages($p, $uploadedImages);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();

        return $p;
    }

    public function update(Product $product, ?Category $category, ?string $title, ?string $description, ?int $priceMinor, ?string $currency, ?ProductStatus $status): void
    {
        if ($category !== null) {
            $product->setCategory($category);
        }
        if ($title !== null && $title !== $product->getTitle()) {
            $product->setTitle($title);
            $base = $this->slugger->slug($title)->lower()->toString();
            if ($base === '') {
                $base = 'product';
            }
            $product->setSlug($this->uniqueSlug($base));
        }
        if ($description !== null) {
            $product->setDescription($description);
        }
        if ($priceMinor !== null) {
            $product->setPriceMinor($priceMinor);
        }
        if ($currency !== null) {
            $product->setCurrency(strtoupper(substr($currency, 0, 3)));
        }
        if ($status !== null) {
            $product->setStatus($status);
        }
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();
    }

    public function delete(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();
    }

    /** @param list<UploadedFile> $uploadedImages */
    public function addImages(Product $product, array $uploadedImages): void
    {
        $this->attachImages($product, $uploadedImages);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['categoryId'])) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', (int) $filters['categoryId']);
        }
        if (!empty($filters['sellerId'])) {
            $qb->andWhere('s.id = :sid')->setParameter('sid', (int) $filters['sellerId']);
        }
        if (!empty($filters['search'])) {
            $qb->andWhere('LOWER(p.title) LIKE :q')->setParameter('q', '%'.mb_strtolower((string) $filters['search']).'%');
        }
        if (isset($filters['minPrice'])) {
            $qb->andWhere('p.priceMinor >= :minp')->setParameter('minp', (int) $filters['minPrice']);
        }
        if (isset($filters['maxPrice'])) {
            $qb->andWhere('p.priceMinor <= :maxp')->setParameter('maxp', (int) $filters['maxPrice']);
        }
    }

    private function applySort(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'price_asc' => $qb->orderBy('p.priceMinor', 'ASC')->addOrderBy('p.id', 'ASC'),
            'price_desc' => $qb->orderBy('p.priceMinor', 'DESC')->addOrderBy('p.id', 'DESC'),
            default => $qb->orderBy('p.createdAt', 'DESC')->addOrderBy('p.id', 'DESC'),
        };
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while ($this->products->findOneBy(['slug' => $slug]) !== null) {
            $slug = $base.'-'.$i;
            ++$i;
        }

        return $slug;
    }

    /** @param list<UploadedFile> $uploadedImages */
    private function attachImages(Product $product, array $uploadedImages): void
    {
        $dir = $this->projectDir.'/public/'.self::UPLOAD_DIR.'/'.$product->getId();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $order = $product->getImages()->count();
        foreach ($uploadedImages as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $ext = $file->guessExtension() ?: 'bin';
            $name = bin2hex(random_bytes(8)).'.'.$ext;
            $file->move($dir, $name);
            $path = '/'.self::UPLOAD_DIR.'/'.$product->getId().'/'.$name;
            $img = (new ProductImage())->setPath($path)->setSortOrder($order++);
            $product->addImage($img);
            $this->em->persist($img);
        }
    }
}
