<?php

declare(strict_types=1);

namespace App\Service\Catalog;

use App\ApiResource\CategoryResource;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\Cache\ListingCacheKeys;
use App\Service\Cache\ListingCacheInvalidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class CategoryService
{
    public function __construct(
        private CategoryRepository $categories,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private ListingCacheInvalidator $cacheInvalidator,
        private TagAwareCacheInterface $tagAwareCache,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listPublicTree(): array
    {
        return $this->tagAwareCache->get(ListingCacheKeys::CATEGORIES, function (ItemInterface $item) {
            $item->tag(['category_tree']);
            $entities = $this->categories->findActiveOrdered();

            return CategoryResource::collection($entities);
        });
    }

    public function create(string $name, ?string $description, ?Category $parent): Category
    {
        $base = $this->slugger->slug($name)->lower()->toString();
        if ($base === '') {
            $base = 'category';
        }
        $slug = $this->uniqueSlug($base);
        $c = (new Category())
            ->setName($name)
            ->setSlug($slug)
            ->setDescription($description)
            ->setParent($parent)
            ->setIsActive(true);
        $this->em->persist($c);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();

        return $c;
    }

    public function update(Category $category, string $name, ?string $description, bool $isActive, ?Category $parent): void
    {
        if ($category->getName() !== $name) {
            $base = $this->slugger->slug($name)->lower()->toString();
            if ($base === '') {
                $base = 'category';
            }
            $category->setSlug($this->uniqueSlug($base));
        }
        $category->setName($name)->setDescription($description)->setIsActive($isActive)->setParent($parent);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();
    }

    public function delete(Category $category): void
    {
        $this->em->remove($category);
        $this->em->flush();
        $this->cacheInvalidator->bumpCatalogCaches();
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while ($this->categories->findOneBySlug($slug) !== null) {
            $slug = $base.'-'.$i;
            ++$i;
        }

        return $slug;
    }
}
