<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Indexer\Product\Fulltext\Datasource;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\AttributeConfig;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Class AdditionalAttributes
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Indexer\Product\Fulltext\Datasource
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class AdditionalAttributes implements DatasourceInterface
{
    /**
     * Resource connection
     *
     * @var ResourceConnection $resource
     */
    protected $resource;
    /**
     * Attribute config
     *
     * @var AttributeConfig $attributeConfig
     */
    protected $attributeConfig;
    /**
     * Attribute repository interface
     *
     * @var AttributeRepositoryInterface $eavAttributeRepositoryInterface
     */
    protected $eavAttributeRepositoryInterface;
    /**
     * Object manager interface
     *
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;
    /**
     * Emulation
     *
     * @var Emulation $appEmulation
     */
    protected $appEmulation;
    /**
     * Area list
     *
     * @var AreaList $areaList
     */
    protected $areaList;

    /**
     * AdditionalAttributes constructor.
     *
     * @param Emulation                    $appEmulation
     * @param AreaList                     $areaList
     * @param ObjectManagerInterface       $objectManager
     * @param AttributeConfig              $attributeConfig
     * @param AttributeRepositoryInterface $eavAttributeRepositoryInterface
     * @param ResourceConnection           $resource
     */
    public function __construct(
        Emulation $appEmulation,
        AreaList $areaList,
        ObjectManagerInterface $objectManager,
        AttributeConfig $attributeConfig,
        AttributeRepositoryInterface $eavAttributeRepositoryInterface,
        ResourceConnection $resource
    ) {
        $this->resource                        = $resource;
        $this->attributeConfig                 = $attributeConfig;
        $this->eavAttributeRepositoryInterface = $eavAttributeRepositoryInterface;
        $this->objectManager                   = $objectManager;
        $this->appEmulation                    = $appEmulation;
        $this->areaList                        = $areaList;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId);
        $area = $this->areaList->getArea(Area::AREA_FRONTEND);
        $area->load(Area::PART_TRANSLATE);

        /** @var string $attributeCode */
        foreach ($this->attributeConfig->getAdditionalSelectedAttributes() as $attributeCode) {
            foreach (array_keys($indexData) as $productId) {
                $indexData[$productId][$attributeCode] = '';
            }

            $attribute = $this->eavAttributeRepositoryInterface->get(Product::ENTITY, $attributeCode);
            $values    = $this->getValueFromAttributes((int)$storeId, $attribute, array_keys($indexData));
            if ($attribute->getSourceModel() !== null) {
                $values = $this->loadSourceModelValue($values, $attribute);
            }
            foreach ($values as $value) {
                $indexData[(int)$value['entity_id']][$attributeCode] = $value['value'];
            }
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return $indexData;
    }

    /**
     * Get request path data
     *
     * @param int                $storeId
     * @param AttributeInterface $attribute
     * @param array              $productIds
     *
     * @return string[]
     */
    protected function getValueFromAttributes(int $storeId, AttributeInterface $attribute, array $productIds): array
    {
        if (!in_array($attribute->getBackendType(), ['datetime', 'decimal', 'int', 'varchar'])) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $tableName  = $connection->getTableName('catalog_product_entity_' . $attribute->getBackendType());
        $select     = $connection->select()->from(
            ['cpe_default' => $tableName],
            []
        )->joinLeft(
            ['cpe' => $tableName],
            'cpe.attribute_id = cpe_default.attribute_id AND
            cpe.entity_id = cpe_default.entity_id AND
            cpe.store_id = ' . $storeId,
            []
        )->columns([
            'value'     => new \Zend_Db_Expr('IFNULL(`cpe`.`value`, cpe_default.value)'),
            'entity_id' => new \Zend_Db_Expr('IFNULL(`cpe`.`entity_id`, cpe_default.entity_id)'),
        ])
            ->where('cpe_default.store_id = 0')
            ->where('cpe_default.entity_id IN (?)', $productIds)
            ->where('cpe_default.attribute_id = ?', $attribute->getId());

        return $connection->fetchAll($select);
    }

    /**
     * Load source model value
     *
     * @param array              $values
     * @param AttributeInterface $attribute
     *
     * @return mixed
     */
    protected function loadSourceModelValue(array $values, AttributeInterface $attribute): array
    {
        $sourceModel  = $this->objectManager->get($attribute->getSourceModel());
        $options      = [];
        $optionsArray = $sourceModel->toOptionArray();
        foreach ($optionsArray as $row) {
            $options[$row['value']] = $row['label'];
        }

        foreach ($values as &$value) {
            if (isset($options[$value['value']])) {
                $value['value'] = (string)$options[$value['value']];
            }
        }

        return $values;
    }
}
