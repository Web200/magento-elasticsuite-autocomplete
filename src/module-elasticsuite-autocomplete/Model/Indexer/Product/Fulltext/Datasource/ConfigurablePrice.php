<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Indexer\Product\Fulltext\Datasource;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Class ConfigurablePrice
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Indexer\Product\Fulltext\Datasource
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class ConfigurablePrice extends Indexer implements DatasourceInterface
{
    /**
     * Dimension collection factory
     *
     * @var DimensionCollectionFactory $dimensionCollectionFactory
     */
    protected $dimensionCollectionFactory;
    /**
     * Dimensions
     *
     * @var string[] $dimensions
     */
    protected $dimensions;
    /**
     * Dimension by website
     *
     * @var string[] $dimensionsByWebsite
     */
    protected $dimensionsByWebsite = [];
    /**
     * Resource connection
     *
     * @var ResourceConnection $resource
     */
    protected $resource;
    /**
     * Price table resolver
     *
     * @var PriceTableResolver $priceTableResolver
     */
    protected $priceTableResolver;

    /**
     * ConfigurablePrice constructor.
     *
     * @param ResourceConnection         $resource
     * @param StoreManagerInterface      $storeManager
     * @param MetadataPool               $metadataPool
     * @param DimensionCollectionFactory $dimensionCollectionFactory
     * @param PriceTableResolver         $priceTableResolver
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        DimensionCollectionFactory $dimensionCollectionFactory,
        PriceTableResolver $priceTableResolver
    ) {
        parent::__construct($resource, $storeManager, $metadataPool);

        $this->resource                   = $resource;
        $this->priceTableResolver         = $priceTableResolver;
        $this->dimensionCollectionFactory = $dimensionCollectionFactory;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $configurableIds = [];
        /** @var string[] $data */
        foreach ($indexData as $data) {
            if ($data['type_id'] !== 'configurable') {
                continue;
            }
            $configurableIds[] = (int)$data['entity_id'];
        }

        if (empty($configurableIds)) {
            return $indexData;
        }

        $websiteId = (int)$this->getStore($storeId)->getWebsiteId();
        $minFinalPriceData = $this->getMinFinalPrice($configurableIds, $websiteId);

        $pricesByEntity = [];
        foreach ($minFinalPriceData as $row) {
            $pricesByEntity[$row['entity_id']][$row['customer_group_id']] = $row['original_price'];
        }

        foreach ($indexData as &$productData) {
            $entityId = (int)$productData['entity_id'];

            if (!isset($productData['price'])) {
                continue;
            }

            foreach ($productData['price'] as &$priceRow) {
                $customerGroupId = $priceRow['customer_group_id'] ?? null;

                if ($customerGroupId !== null && isset($pricesByEntity[$entityId][$customerGroupId])) {
                    $priceRow['original_price'] = (float)$pricesByEntity[$entityId][$customerGroupId];
                } elseif (!isset($priceRow['original_price']) && isset($priceRow['price'])) {
                    $priceRow['original_price'] = (float)$priceRow['price'];
                }
            }
        }

        return $indexData;
    }

    /**
     * Get min final price
     *
     * @param array $parentIds
     * @param int   $websiteId
     *
     * @return array
     */
    protected function getMinFinalPrice(array $parentIds, int $websiteId): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()->from(
            ['parent' => $this->resource->getTableName('catalog_product_entity')],
            []
        )->join(
            ['link' => $this->resource->getTableName('catalog_product_relation')],
            'link.parent_id = parent.entity_id',
            []
        )->join(
            ['t' => $this->resource->getTableName($this->getPriceIndexDimensionsTables($websiteId))],
            't.entity_id = link.child_id',
            []
        )->join(
            ['csi' => $connection->getTableName('cataloginventory_stock_item')],
            'csi.product_id = link.child_id',
            []
        )->columns([
            new \Zend_Db_Expr('t.customer_group_id'),
            new \Zend_Db_Expr('parent.entity_id'),
            new \Zend_Db_Expr('MIN(price) as original_price')
        ])
            ->where('parent.entity_id IN (?)', $parentIds)
            ->where('t.price > 0')
            ->group(['parent.entity_id', 't.customer_group_id']);

        return $connection->fetchAll($select);
    }

    /**
     * Return the price index tables according to the price index dimensions for the given website.
     *
     * @param integer $websiteId Website id.
     *
     * @return string
     */
    private function getPriceIndexDimensionsTables(int $websiteId): string
    {
        $tables = [];

        $indexDimensions = $this->getPriceIndexDimensions($websiteId);
        foreach ($indexDimensions as $dimensions) {
            $tables[] = $this->priceTableResolver->resolve('catalog_product_index_price', $dimensions);
        }

        return $tables[0];
    }

    /**
     * Return price index dimensions applicable for the given website.
     *
     * @param integer $websiteId
     *
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getPriceIndexDimensions(int $websiteId)
    {
        if (!array_key_exists($websiteId, $this->dimensionsByWebsite)) {
            $indexDimensions = $this->getAllPriceIndexDimensions();

            $relevantDimensions = [];
            foreach ($indexDimensions as $dimensions) {
                if (array_key_exists(WebsiteDimensionProvider::DIMENSION_NAME, $dimensions)) {
                    $websiteDimension = $dimensions[WebsiteDimensionProvider::DIMENSION_NAME];
                    if ((string)$websiteDimension->getValue() == $websiteId) {
                        $relevantDimensions[] = $dimensions;
                    }
                } else {
                    $relevantDimensions[] = $dimensions;
                }
            }

            $this->dimensionsByWebsite[$websiteId] = $relevantDimensions;
        }

        return $this->dimensionsByWebsite[$websiteId];
    }

    /**
     * Return all price index dimensions.
     *
     * @return array
     */
    private function getAllPriceIndexDimensions()
    {
        if ($this->dimensions === null) {
            $this->dimensions = $this->dimensionCollectionFactory->create();
        }

        return $this->dimensions;
    }
}
