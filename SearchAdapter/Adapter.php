<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\SearchAdapter;

use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder as AggregationBuilder;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\QueryContainerFactory;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Psr\Log\LoggerInterface;
use Recomdoai\Core\Helper\Connection;

/**
 * OpenSearch Search Adapter
 */
class Adapter implements AdapterInterface
{
    /**
     * Mapper instance
     *
     * @var Mapper
     */
    private $mapper;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    private $aggregationBuilder;

    /**
     * @var QueryContainerFactory
     */
    private $queryContainerFactory;

    /**
     * Empty response from OpenSearch
     *
     * @var array
     */
    private static $emptyRawResponse = [
        'hits' => [
            'hits' => []
        ],
        'aggregations' => [
            'price_bucket' => [],
            'category_bucket' => [
                'buckets' => []
            ]
        ]
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        Connection            $connectionhelper,
        Mapper                $mapper,
        ResponseFactory       $responseFactory,
        AggregationBuilder    $aggregationBuilder,
        QueryContainerFactory $queryContainerFactory,
        LoggerInterface       $logger
    )
    {
        $this->connecthelper = $connectionhelper;
        $this->mapper = $mapper;
        $this->responseFactory = $responseFactory;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->queryContainerFactory = $queryContainerFactory;
        $this->logger = $logger;
    }

    /**
     * Search query
     *
     * @param RequestInterface $request
     * @return QueryResponse
     */
    public function query(RequestInterface $request): QueryResponse
    {
        $aggregationBuilder = $this->aggregationBuilder;
        $query = $this->mapper->buildQuery($request);
        $aggregationBuilder->setQuery($this->queryContainerFactory->create(['query' => $query]));

        $searchResult = $this->connecthelper->callAPI('search/recomdoai_api/search_with_suggestions?keyword=' . $request->getQuery()->getShould()['search']->getValue());

        try {
            $rawResponse = self::$emptyRawResponse;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            // return empty search result in case an exception is thrown from OpenSearch
            $rawResponse = self::$emptyRawResponse;
        }

    }
}
