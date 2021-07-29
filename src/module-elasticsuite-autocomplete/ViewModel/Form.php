<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

/**
 * Class Success
 *
 * @package Mcc\Checkout\ViewModel
 */
class Form implements ArgumentInterface
{
    /**
     * Store manager interface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * Url interface
     *
     * @var UrlInterface $urlBuilder
     */
    protected $urlBuilder;
    /**
     * ScopeConfigInterface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Form constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface  $scopeConfig
     * @param UrlInterface          $urlBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->urlBuilder   = $urlBuilder;
        $this->scopeConfig  = $scopeConfig;
    }

    /**
     * Get search url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSearchUrl(): string
    {
        $useStoreInUrl = (bool)$this->scopeConfig->getValue(Store::XML_PATH_STORE_IN_URL, ScopeInterface::SCOPE_STORE);
        if (!$useStoreInUrl) {
            return $this->urlBuilder->getUrl('', ['_direct' => 'search.php']);
        }

        return '/search.php?store_code=' . $this->storeManager->getStore()->getCode();
    }
}
