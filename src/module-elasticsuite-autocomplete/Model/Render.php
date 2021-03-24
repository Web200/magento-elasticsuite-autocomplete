<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Web200\ElasticsuiteAutocomplete\Model\Render\Product;

/**
 * Class Render
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Render
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
     * @var Product $productRender
     */
    protected $productRender;
    /**
     * Query factory
     *
     * @var QueryFactory $queryFactory
     */
    protected $queryFactory;

    /**
     * Render constructor.
     *
     * @param Builder               $requestBuilder
     * @param Mapper                $mapper
     * @param CacheInterface        $cache
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param Product               $productRender
     * @param QueryFactory          $queryFactory
     * @param Client                $client
     */
    public function __construct(
        Builder $requestBuilder,
        Mapper $mapper,
        CacheInterface $cache,
        StoreManagerInterface $storeManager,
        Json $json,
        Product $productRender,
        QueryFactory $queryFactory,
        Client $client
    ) {
        $this->client         = $client;
        $this->requestMapper  = $mapper;
        $this->requestBuilder = $requestBuilder;
        $this->storeManager   = $storeManager;
        $this->cache          = $cache;
        $this->json           = $json;
        $this->productRender  = $productRender;
        $this->queryFactory = $queryFactory;
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
     */
    protected function buildQuery()
    {
        /** @var int $storeId */
        $storeId = $this->storeManager->getStore()->getId();
        /** @var string $key */
        $key = 'smile_autocomplete_query:' . $storeId;
        /** @var string $searchRequestJson */
        $searchRequestJson = $this->cache->load($key);
        if ($searchRequestJson === false) {
            /** @var string[] $facets */
            $facets  = [
                ['name' => 'attribute_set_id', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
                ['name' => 'indexed_attributes', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
            ];
            $request = $this->requestBuilder->create(
                $storeId,
                'catalog_product_autocomplete',
                0,
                10,
                'product',
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
            $this->cache->save($this->json->serialize($searchRequest), $key, ['autocomplete'], 3600);

            return $searchRequest;
        }

        return $this->json->unserialize($searchRequestJson);
    }

    /**
     * Query
     *
     * @return string[]
     */
    protected function query(): array
    {
        /** @var string $searchString */
        $searchString = $this->queryFactory->get()->getQueryText();

        /** @var string[] $query */
        $query = $this->buildQuery();
        if (isset($query['body']['query']['bool']['must']['bool']['filter']['multi_match'])) {
            $query['body']['query']['bool']['must']['bool']['filter']['multi_match']['query'] = $searchString;
            $query['body']['query']['bool']['must']['bool']['must']['multi_match']['query']   = $searchString;
        }

        return $this->client->search($query);
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
