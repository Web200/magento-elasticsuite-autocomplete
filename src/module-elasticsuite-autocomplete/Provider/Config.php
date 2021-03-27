<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Provider;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 *
 * @package   Web200\ElasticsuiteAutocomplete\Provider
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Config
{
    public const CATEGORY_ACTIVE = 'smile_elasticsuite_autocomplete_settings/category_autocomplete/is_active';
    public const PRODUCT_ACTIVE = 'smile_elasticsuite_autocomplete_settings/product_autocomplete/is_active';
    public const PRODUCT_MAX_SIZE = 'smile_elasticsuite_autocomplete_settings/product_autocomplete/max_size';
    public const CATEGORY_MAX_SIZE = 'smile_elasticsuite_autocomplete_settings/category_autocomplete/max_size';
    /**
     * Scope config interface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Query constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is category active
     *
     * @param null $store
     *
     * @return bool
     */
    public function isCategoryActive($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CATEGORY_ACTIVE,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Is product active
     *
     * @param null $store
     *
     * @return bool
     */
    public function isProductActive($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::PRODUCT_ACTIVE,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Get product autocomplete max size
     *
     * @param null $store
     *
     * @return int|null
     */
    public function getProductAutocompleteMaxSize($store = null): ?int
    {
        return (int)$this->scopeConfig->getValue(
            self::PRODUCT_MAX_SIZE,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Get category autocomplete max size
     *
     * @param null $store
     *
     * @return int|null
     */
    public function getCategoryAutocompleteMaxSize($store = null): ?int
    {
        return (int)$this->scopeConfig->getValue(
            self::CATEGORY_MAX_SIZE,
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }
}
