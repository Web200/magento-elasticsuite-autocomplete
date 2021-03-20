<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Render;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Product
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Product
{
    /**
     * Product url
     *
     * @var Url $productUrl
     */
    protected $productUrl;
    /**
     * scopeConfig
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * Store manager
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * Image helper
     *
     * @var $imageHelper
     */
    protected $imageHelper;
    /**
     * Product factory
     *
     * @var ProductFactory $productFactory
     */
    protected $productFactory;
    /**
     * customerSession
     *
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * Product constructor.
     *
     * @param Url                   $productUrl
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Image                 $imageHelper
     * @param Session               $customerSession
     * @param ProductFactory        $productFactory
     */
    public function __construct(
        Url $productUrl,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        Session $customerSession,
        ProductFactory $productFactory
    ) {
        $this->productUrl     = $productUrl;
        $this->scopeConfig    = $scopeConfig;
        $this->storeManager   = $storeManager;
        $this->imageHelper    = $imageHelper;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Render
     *
     * @param string[] $productData
     *
     * @return string[]
     */
    public function render(array $productData)
    {
        /** @var string[] $product */
        $product = [];

        $product['type']  = 'product';
        $product['title'] = $productData['_source']['name'][0];
        $product['url']   = $this->generateProductUrl($this->getFirstResult($productData['_source']['url_key']));
        $product['sku']   = $this->getFirstResult($productData['_source']['sku']);
        $product['image'] = $this->generateImageUrl($this->getFirstResult($productData['_source']['image']));
        $product['price'] = $this->getPrice($productData);

        //$product['type_id'] = $productData['_source']['type_id'];
        //$product['url_key'] = $this->getFirstResult($productData['_source']['url_key']);
        //$product['name']    = $productData['_source']['name'];

        return $product;
    }

    /**
     * Get price
     *
     * @param array $productData
     *
     * @return mixed
     */
    protected function getPrice(array $productData)
    {
        /** @var string[] $prices */
        $prices = [];
        foreach ($productData['_source']['price'] as $price) {
            $prices[$price['customer_group_id']] = $price;
        }

        /** @var int $customerGroupId */
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        if ($customerGroupId > 0 && isset($prices[$customerGroupId])) {
            return $prices[$customerGroupId]['final_price'];
        }

        return $prices[$customerGroupId]['final_price'];
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
     * Generate product url
     *
     * @param string $urlKey
     *
     * @return string
     */
    protected function generateProductUrl(string $urlKey): string
    {
        /** @var string $url */
        $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $urlKey;

        $urlSuffix = (string)$this->scopeConfig->getValue(
            ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE
        );

        if ($urlSuffix !== '') {
            $url .= $urlSuffix;
        }

        return $url;
    }

    /**
     * Generate image url
     *
     * @param string $imagePath
     *
     * @return string
     */
    protected function generateImageUrl(string $imagePath): string
    {
        /** @var ModelProduct $product */
        $product = $this->productFactory->create();

        return $this->imageHelper->init($product, 'smile_elasticsuite_autocomplete_product_image')
            ->setImageFile($imagePath)
            ->getUrl();
    }
}
