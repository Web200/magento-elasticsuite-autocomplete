<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model;

use Web200\ElasticsuiteAutocomplete\Model\Query\Category as QueryCategory;
use Web200\ElasticsuiteAutocomplete\Model\Query\Product as QueryProduct;
use Web200\ElasticsuiteAutocomplete\Provider\Config;

/**
 * Class Query
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Query
{
    /**
     * Query Product
     *
     * @var QueryProduct $productQuery
     */
    protected $productQuery;
    /**
     * Query category
     *
     * @var QueryCategory $categoryQuery
     */
    protected $categoryQuery;
    /**
     * Config
     *
     * @var Config $config
     */
    protected $config;

    /**
     * Query constructor.
     *
     * @param Config        $config
     * @param QueryProduct  $productQuery
     * @param QueryCategory $categoryQuery
     */
    public function __construct(
        Config $config,
        QueryProduct $productQuery,
        QueryCategory $categoryQuery
    ) {
        $this->productQuery  = $productQuery;
        $this->categoryQuery = $categoryQuery;
        $this->config        = $config;
    }

    /**
     * Execute
     *
     * @return mixed
     */
    public function execute()
    {
        /** @var string[] $result */
        $result = [];

        if ($this->config->isProductActive()) {
            $result = array_merge($result, $this->productQuery->execute());
        }
        if ($this->config->isCategoryActive()) {
            $result = array_merge($result, $this->categoryQuery->execute());
        }

        return $result;
    }
}
