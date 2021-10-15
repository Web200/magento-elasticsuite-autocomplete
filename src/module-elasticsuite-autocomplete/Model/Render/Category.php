<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Render;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Category
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Category
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * ScopeConfig
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Product constructor.
     *
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Render
     *
     * @param string[] $categoryData
     *
     * @return string[]
     */
    public function render(array $categoryData)
    {
        /** @var string[] $category */
        $category = [];

        $category['type']       = 'category';
        $category['title']      = $this->getFirstResult($categoryData['_source']['name']);
        $category['url']        = $this->generateCategoryUrl($categoryData);
        $category['breadcrumb'] = explode('/', $categoryData['_source']['breadcrumb']);

        return $category;
    }

    /**
     * Get first result
     *
     * @param $result
     *
     * @return mixed
     */
    protected function getFirstResult($result)
    {
        if (is_array($result)) {
            $result = $result[0];
        }

        return $result;
    }

    /**
     * Generate category url
     *
     * @param array $categoryData
     *
     * @return string
     */
    protected function generateCategoryUrl(array $categoryData): string
    {
        if (isset($productData['_source']['request_path'])) {
            $requestPath =  $this->getFirstResult($categoryData['_source']['request_path']);
        } else {
            $suffix = (string)$this->scopeConfig->getValue(CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX, ScopeInterface::SCOPE_STORE);
            $requestPath = $this->getFirstResult($categoryData['_source']['url_key']) . $suffix;
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $this->getFirstResult($requestPath);
    }
}
