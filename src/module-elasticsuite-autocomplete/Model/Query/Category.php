<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Model\Query;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Web200\ElasticsuiteAutocomplete\Model\Render\Category as CategoryRender;
use Web200\ElasticsuiteAutocomplete\Provider\Config;

/**
 * Class Category
 *
 * @package   Web200\ElasticsuiteAutocomplete\Model\Query
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Category
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
     * Category render
     *
     * @var CategoryRender $categoryRender
     */
    protected $categoryRender;
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
     * Category constructor.
     *
     * @param Config                $config
     * @param Builder               $requestBuilder
     * @param Mapper                $mapper
     * @param CacheInterface        $cache
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param CategoryRender        $categoryRender
     * @param QueryFactory          $queryFactory
     * @param Client                $client
     */
    public function __construct(
        Config $config,
        Builder $requestBuilder,
        Mapper $mapper,
        CacheInterface $cache,
        StoreManagerInterface $storeManager,
        Json $json,
        CategoryRender $categoryRender,
        QueryFactory $queryFactory,
        Client $client
    ) {
        $this->client         = $client;
        $this->requestMapper  = $mapper;
        $this->requestBuilder = $requestBuilder;
        $this->storeManager   = $storeManager;
        $this->cache          = $cache;
        $this->json           = $json;
        $this->categoryRender = $categoryRender;
        $this->queryFactory   = $queryFactory;
        $this->config         = $config;
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
        $key = 'smile_autocomplete_category_query:' . $storeId;
        /** @var string $searchRequestJson */
        $searchRequestJson = $this->cache->load($key);
        if ($searchRequestJson === false) {
            $request = $this->requestBuilder->create(
                $storeId,
                'category_search_container',
                0,
                $this->config->getCategoryAutocompleteMaxSize(),
                'product',
                [],
                [],
                []
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
        if (isset($query['body']['query']['bool']['must']['bool']['should'])) {
            foreach ($query['body']['query']['bool']['must']['bool']['should'] as &$should) {
                $should['multi_match']['query'] = $searchString;
            }
        }

        return $this->client->search($query);
    }

    /**
     * Parse query
     *
     * @param string[] $result
     *
     * @return string[]
     */
    protected function parseQuery(array $result): array
    {
        if (!isset($result['hits'])) {
            return [];
        }

        /** @var string[] $final */
        $final = [];
        /** @var string[] $category */
        foreach ($result['hits']['hits'] as $category) {
            if (!isset($category['_source'])) {
                continue;
            }
            $final[] = $this->categoryRender->render($category);
        }

        return $final;
    }
}
