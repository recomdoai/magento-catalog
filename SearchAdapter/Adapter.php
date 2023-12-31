<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\SearchAdapter;

use Magento\Store\Model\StoreManagerInterface;
use Recomdoai\Catalog\SearchAdapter\Aggregation\Builder as AggregationBuilder;
use Magento\Elasticsearch\SearchAdapter\QueryContainerFactory;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Psr\Log\LoggerInterface;
use Recomdoai\Core\Helper\Connection;

class Adapter implements AdapterInterface
{
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


    public function __construct(
        Connection            $connectionhelper,
        Mapper                $mapper,
        ResponseFactory       $responseFactory,
        AggregationBuilder    $aggregationBuilder,
        QueryContainerFactory $queryContainerFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface       $logger
    )
    {
        $this->connecthelper = $connectionhelper;
        $this->mapper = $mapper;
        $this->responseFactory = $responseFactory;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->queryContainerFactory = $queryContainerFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function query(RequestInterface $request): QueryResponse
    {
        $aggregationBuilder = $this->aggregationBuilder;
        $query = $this->mapper->buildQuery($request);
        $aggregationBuilder->setQuery($this->queryContainerFactory->create(['query' => $query]));

        $queryString = json_encode($query);
        try {
            $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->storeManager->getStore()->getCode() . '/search/?searchCriteria=' . urlencode($queryString));

            if (!isset($rawResponse['data']) || empty($rawResponse['data'])) {
                $rawResponse['data'] = self::$emptyRawResponse;
            }

        } catch (\Exception $e) {
            $this->logger->critical($e);
            // return empty search result in case an exception is thrown from OpenSearch
            $rawResponse['data'] = self::$emptyRawResponse;
        }

        $rawDocuments = $rawResponse['data']['hits']['hits'] ?? [];
        $queryResponse = $this->responseFactory->create(
            [
                'documents' => $rawDocuments,
                'aggregations' => $aggregationBuilder->build($request, $rawResponse['data']),
                'total' => $rawResponse['data']['hits']['total']['value'] ?? 0
            ]
        );
        return $queryResponse;
    }
}
