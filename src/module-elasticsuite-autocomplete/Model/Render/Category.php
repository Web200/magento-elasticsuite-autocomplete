<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Render;

use Magento\Framework\UrlInterface;
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
     * Product constructor.
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
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
        $category['url']        = $this->generateCategoryUrl($this->getFirstResult($categoryData['_source']['request_path']));
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
     * @param string $requestPath
     *
     * @return string
     */
    protected function generateCategoryUrl(string $requestPath): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $requestPath;
    }
}
