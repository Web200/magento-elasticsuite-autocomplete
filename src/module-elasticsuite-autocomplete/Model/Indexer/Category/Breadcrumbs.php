<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Indexer\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Breadcrumbs
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Indexer\Category
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Breadcrumbs
{
    /**
     * Cache id categories
     *
     * @var string CACHE_ID_CATEGORIES
     */
    protected const CACHE_ID_CATEGORIES = 'elasticautocomplete_categories';
    /**
     * Store categories
     *
     * @var string[] $storeCategories
     */
    protected $storeCategories;
    /**
     * Cache
     *
     * @var CacheInterface $cache
     */
    protected $cache;
    /**
     * Category collection factory
     *
     * @var CollectionFactory $categoryCollectionFactory
     */
    protected $categoryCollectionFactory;
    /**
     * Json
     *
     * @var Json $json
     */
    protected $json;
    /**
     * StoreManager Interface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Breadcrumbs constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param CollectionFactory     $categoryCollectionFactory
     * @param CacheInterface        $cache
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Json $json,
        CollectionFactory $categoryCollectionFactory,
        CacheInterface $cache
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->cache                     = $cache;
        $this->json                      = $json;
        $this->storeManager              = $storeManager;
    }

    /**
     * buildCategoryPath
     *
     * @param     $categoryPath
     * @param int $storeId
     *
     * @return string
     */
    public function getCategoryFromPath($categoryPath, int $storeId): string
    {
        /* first 2 categories can be ignored */
        $categoryIds         = array_slice(explode('/', $categoryPath), 2);
        $categoriesWithNames = [];
        $storeCategories     = $this->getStoreCategories($storeId);
        foreach ($categoryIds as $categoryId) {
            if (isset($storeCategories[$categoryId])) {
                $categoriesWithNames[] = $storeCategories[$categoryId]['name'];
            }
        }

        return implode('/', $categoriesWithNames);
    }

    /**
     * Get store categories
     *
     * @param int $storeId
     *
     * @return string[]|mixed
     */
    protected function getStoreCategories(int $storeId): array
    {
        if ($this->storeCategories === null) {
            $this->storeCategories = [];
            $rootCategoryId        = $this->storeManager->getStore($storeId)->getRootCategoryId();
            $cacheKey              = self::CACHE_ID_CATEGORIES . '-' . $rootCategoryId . '-' . $storeId;
            $cacheCategory         = $this->cache->load($cacheKey);
            if (!$cacheCategory) {
                $categories = $this->categoryCollectionFactory->create()
                    ->setStoreId($storeId)
                    ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
                    ->addAttributeToSelect('name');
                /** @var Category $categ */
                foreach ($categories as $categ) {
                    $this->storeCategories[$categ->getData('entity_id')] = [
                        'name' => $categ->getData('name'),
                        'path' => $categ->getData('path')
                    ];
                }
                $cacheCategory = $this->json->serialize($this->storeCategories);
                $this->cache->save($cacheCategory, $cacheKey);
            }

            $this->storeCategories = $this->json->unserialize($cacheCategory, true);
        }

        return $this->storeCategories;
    }
}
