<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Render;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\AttributeConfig;

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
     * Customer session
     *
     * @var Session $customerSession
     */
    protected $customerSession;
    /**
     * Attribute config
     *
     * @var AttributeConfig
     */
    protected $attributeConfig;
    /**
     * Data
     *
     * @var Data $priceHelper
     */
    protected $priceHelper;
    /**
     * scopeConfig
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Product constructor.
     *
     * @param AttributeConfig       $attributeConfig
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Data                  $priceHelper
     * @param Image                 $imageHelper
     * @param Session               $customerSession
     * @param ProductFactory        $productFactory
     */
    public function __construct(
        AttributeConfig $attributeConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Data $priceHelper,
        Image $imageHelper,
        Session $customerSession,
        ProductFactory $productFactory
    ) {
        $this->storeManager    = $storeManager;
        $this->imageHelper     = $imageHelper;
        $this->productFactory  = $productFactory;
        $this->customerSession = $customerSession;
        $this->attributeConfig = $attributeConfig;
        $this->priceHelper     = $priceHelper;
        $this->scopeConfig = $scopeConfig;
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
        $product            = [];
        $product['type']    = 'product';
        $product['type_id'] = $productData['_source']['type_id'];
        $product['title']   = $productData['_source']['name'][0];
        $product['url']     = $this->generateProductUrl($productData);
        $product['sku']     = $this->getFirstResult($productData['_source']['sku']);
        $product['image']   = $this->generateImageUrl($this->getFirstResult($productData['_source']['image']));
        list($product['regular_price_value'], $product['price_value'], $product['promotion_percentage']) = $this->getPriceValue($productData);
        $product['price']         = $this->getPrice($product, 'price_value');
        $product['regular_price'] = $this->getPrice($product, 'regular_price_value');

        $additionalAttributes = $this->attributeConfig->getAdditionalSelectedAttributes();
        foreach ($additionalAttributes as $key) {
            if (isset($productData['_source'][$key])) {
                $product[$key] = $productData['_source'][$key];
            }
        }

        return $product;
    }

    /**
     * Get price
     *
     * @param array $productData
     *
     * @return mixed
     */
    protected function getPriceValue(array $productData)
    {
        if (!isset($productData['_source']['price'])) {
            return ['', '', ''];
        }

        /** @var string[] $prices */
        $prices = [];
        foreach ($productData['_source']['price'] as $price) {
            $prices[$price['customer_group_id']] = $price;
        }

        /** @var int $customerGroupId */
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        if ($customerGroupId >= 0 && isset($prices[$customerGroupId])) {
            $regularPrice = $prices[$customerGroupId]['original_price'];
            $finalPrice   = $prices[$customerGroupId]['price'];
            $promotion    = 0;
            if ($regularPrice != $finalPrice && $regularPrice > 0) {
                $promotion = round(100 - ($finalPrice * 100 / $regularPrice), 2);
            }

            return [
                $regularPrice,
                $finalPrice,
                $promotion
            ];
        }

        return ['', '', ''];
    }

    /**
     * Get price
     *
     * @param string[] $product
     * @param string   $key
     *
     * @return string
     */
    protected function getPrice(array $product, string $key): string
    {
        $price = $this->priceHelper->currency($product[$key]);

        if ($product['type_id'] === 'configurable') {
            return __('As low as') . ' ' . $price;
        }

        return $price;
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
     * @param array $productData
     *
     * @return string
     */
    protected function generateProductUrl(array $productData): string
    {
        if (isset($productData['_source']['request_path'])) {
            $requestPath =  $this->getFirstResult($productData['_source']['request_path']);
        } else {
            $suffix = (string)$this->scopeConfig->getValue(ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX, ScopeInterface::SCOPE_STORE);
            $requestPath = $this->getFirstResult($productData['_source']['url_key']) .".html";
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $requestPath;
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
        $product = $this->productFactory->create();

        return $this->imageHelper->init($product, 'smile_elasticsuite_autocomplete_product_image')
            ->setImageFile($imagePath)
            ->getUrl();
    }
}
