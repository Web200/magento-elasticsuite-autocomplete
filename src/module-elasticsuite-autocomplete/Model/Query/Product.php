<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Query;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Search\Model\Autocomplete\Item as TermItem;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Web200\ElasticsuiteAutocomplete\Model\Render\Product as RenderProduct;
use Web200\ElasticsuiteAutocomplete\Provider\Config;

/**
 * Class Product
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Query
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Product
{
    /**
     * Client
     *
     * @var Client $client
     */
    protected $client;
    /**
     * Mapper
     *
     * @var Mapper $requestMapper
     */
    protected $requestMapper;
    /**
     * Request builder
     *
     * @var Builder $requestBuilder
     */
    protected $requestBuilder;
    /**
     * Store manager interface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * Cache
     *
     * @var CacheInterface $cache
     */
    protected $cache;
    /**
     * Json
     *
     * @var Json $json
     */
    protected $json;
    /**
     * Product render
     *
     * @var RenderProduct $productRender
     */
    protected $productRender;
    /**
     * Query factory
     *
     * @var QueryFactory $queryFactory
     */
    protected $queryFactory;
    /**
     * Config
     *
     * @var Config $config
     */
    protected $config;
    /**
     * Config
     *
     * @var TermDataProvider $termDataProvider
     */
    protected $termDataProvider;

    /**
     * Product constructor.
     *
     * @param Config                $config
     * @param Builder               $requestBuilder
     * @param Mapper                $mapper
     * @param CacheInterface        $cache
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param RenderProduct         $productRender
     * @param QueryFactory          $queryFactory
     * @param Client                $client
     * @param TermDataProvider      $termDataProvider
     */
    public function __construct(
        Config $config,
        Builder $requestBuilder,
        Mapper $mapper,
        CacheInterface $cache,
        StoreManagerInterface $storeManager,
        Json $json,
        RenderProduct $productRender,
        QueryFactory $queryFactory,
        Client $client,
        TermDataProvider $termDataProvider
    ) {
        $this->client           = $client;
        $this->requestMapper    = $mapper;
        $this->requestBuilder   = $requestBuilder;
        $this->storeManager     = $storeManager;
        $this->cache            = $cache;
        $this->json             = $json;
        $this->productRender    = $productRender;
        $this->queryFactory     = $queryFactory;
        $this->config           = $config;
        $this->termDataProvider = $termDataProvider;
    }

    /**
     * Execute
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->parseQuery($this->query());
    }

    /**
     * BuildQuery
     *
     * @return string[]
     * @throws NoSuchEntityException
     */
    protected function buildQuery()
    {
        /** @var int $storeId */
        $storeId = $this->storeManager->getStore()->getId();

        /** @var string[] $facets */
        $facets  = [
            ['name' => 'attribute_set_id', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
            ['name' => 'indexed_attributes', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
        ];
        $request = $this->requestBuilder->create(
            $storeId,
            'catalog_product_autocomplete',
            0,
            $this->config->getProductAutocompleteMaxSize(),
            $this->getSearchTerm(),
            [],
            [],
            [],
            $facets
        );
        /** @var string[] $searchRequest */
        $searchRequest = [
            'index' => $request->getIndex(),
            'body'  => $this->requestMapper->buildSearchRequest($request),
        ];

        return $searchRequest;
    }

    /**
     * Get search Term
     *
     * @return array|null[]|string[]
     */
    protected function getSearchTerm()
    {
        $terms = array_map(
            function (TermItem $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        if (empty($terms)) {
            $terms = [$this->queryFactory->get()->getQueryText()];
        }

        return $terms;
    }

    /**
     * Query
     *
     * @return string[]
     */
    protected function query(): array
    {
        return $this->client->search($this->buildQuery());
    }

    /**
     * Parse query
     *
     * @return array
     */
    protected function parseQuery(array $result): array
    {
        if (!isset($result['hits'])) {
            return [];
        }

        /** @var string[] $final */
        $final = [];
        foreach ($result['hits']['hits'] as $product) {
            if (!isset($product['_source'])) {
                continue;
            }
            $final[] = $this->productRender->render($product);
        }

        return $final;
    }
}
