<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Indexer\Category\Fulltext\Datasource;

use Magento\Framework\App\ResourceConnection;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Class Url
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Indexer\Category\Fulltext\Datasource
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Url implements DatasourceInterface
{
    /**
     * Resource connection
     *
     * @var ResourceConnection $resource
     */
    protected $resource;

    /**
     * Url constructor.
     *
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $requestPathData = $this->getRequestPathData((int)$storeId);
        /** @var string[] $data */
        foreach ($requestPathData as $data) {
            if (isset($indexData[(int)$data['entity_id']])) {
                $indexData[(int)$data['entity_id']]['request_path'] = $data['request_path'];
            }
        }

        return $indexData;
    }

    /**
     * Get request path data
     *
     * @param int $storeId
     *
     * @return string[]
     */
    protected function getRequestPathData(int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $select     = $connection->select()->from(
            ['url_rewrite' => $this->resource->getTableName('url_rewrite')],
            ['request_path', 'entity_id']
        )->where('entity_type = ?', 'category')
            ->where('store_id = ?', $storeId)
            ->where('redirect_type = 0')
            ->where('metadata IS NULL');

        return $connection->fetchAll($select);
    }
}
