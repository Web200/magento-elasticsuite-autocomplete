<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Indexer\Category\Fulltext\Datasource;

use Magento\Framework\App\ResourceConnection;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Web200\ElasticsuiteAutocomplete\Model\Indexer\Category\Breadcrumbs as CategoryBreadcrumbs;

/**
 * Class Breadcrumbs
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Indexer\Category\Fulltext\Datasource
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Breadcrumbs implements DatasourceInterface
{
    /**
     * Resource connection
     *
     * @var ResourceConnection $resource
     */
    protected $resource;
    /**
     * categoryBreadcrumbs
     *
     * @var CategoryBreadcrumbs $categoryBreadcrumbs
     */
    protected $categoryBreadcrumbs;

    /**
     * Breadcrumbs constructor.
     *
     * @param ResourceConnection  $resource
     * @param CategoryBreadcrumbs $categoryBreadcrumbs
     */
    public function __construct(
        ResourceConnection $resource,
        CategoryBreadcrumbs $categoryBreadcrumbs
    ) {
        $this->resource            = $resource;
        $this->categoryBreadcrumbs = $categoryBreadcrumbs;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        /** @var string[] $data */
        foreach ($indexData as $categoryId => $data) {
            if (!isset($data['path'])) {
                continue;
            }
            $indexData[$categoryId]['breadcrumb'] = $this->categoryBreadcrumbs->getCategoryFromPath(
                $data['path'],
                $storeId
            );
        }

        return $indexData;
    }
}
