<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\SearchAdapter\Aggregation;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Cleaner;
use Magento\Framework\Search\Request\Config;
use Magento\Framework\Search\Request\Mapper;
use Recomdoai\Catalog\SearchAdapter\Aggregation\Builder\BucketBuilderInterface;
use Magento\Elasticsearch\SearchAdapter\QueryContainer;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Dynamic\DataProviderInterface;

/**
 * Elasticsearch aggregation builder
 */
class Builder
{
    /**
     * @var DataProviderInterface[]
     */
    private $dataProviderContainer;

    /**
     * @var BucketBuilderInterface[]
     */
    private $aggregationContainer;

    /**
     * @var DataProviderFactory
     */
    private $dataProviderFactory;

    /**
     * @var QueryContainer
     */
    private $query;

    /**
     * @param DataProviderInterface[] $dataProviderContainer
     * @param BucketBuilderInterface[] $aggregationContainer
     * @param DataProviderFactory $dataProviderFactory
     */
    public function __construct(
        array                  $dataProviderContainer,
        array                  $aggregationContainer,
        DataProviderFactory    $dataProviderFactory,
        Config                 $config,
        ObjectManagerInterface $objectManager,
        Cleaner                $cleaner
    )
    {
        $this->dataProviderContainer = array_map(
            static function (DataProviderInterface $dataProvider) {
                return $dataProvider;
            },
            $dataProviderContainer
        );
        $this->aggregationContainer = array_map(
            static function (BucketBuilderInterface $bucketBuilder) {
                return $bucketBuilder;
            },
            $aggregationContainer
        );
        $this->dataProviderFactory = $dataProviderFactory;
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->cleaner = $cleaner;
    }

    /**
     * Builds aggregations from the search request.
     *
     * This method iterates through buckets and builds all aggregations one by one, passing buckets and relative
     * data into bucket aggregation builders which are responsible for aggregation calculation.
     *
     * @param RequestInterface $request
     * @param array $queryResult
     * @return array
     * @throws \LogicException thrown by DataProviderFactory for validation issues
     * @see \Magento\Elasticsearch\SearchAdapter\Aggregation\DataProviderFactory
     */
    public function build(RequestInterface $request, array $queryResult)
    {
        $aggregations = [];
        $data = $this->config->get('catalog_view_container');

        $sortedData = [];
        foreach ($queryResult['aggregations'] as $key => $value) {
            if (isset($data['aggregations'][$key])) {
                $sortedData[$key] = $data['aggregations'][$key];
            }
        }
        foreach ($data['aggregations'] as $key => $value) {
            if (!isset($queryResult['aggregations'][$key])) {
                $sortedData[$key] = $value;
            }
        }

        $this->clean($sortedData);

        $data['aggregations'] = $this->processData($sortedData);

        $buckets = $this->convert($data);

        foreach ($buckets as $bucket) {
            $dataProvider = $this->dataProviderFactory->create(
                $this->dataProviderContainer[$request->getIndex()],
                $this->query,
                $bucket->getField()
            );
            $bucketAggregationBuilder = $this->aggregationContainer[$bucket->getType()];
            $aggregations[$bucket->getName()] = $bucketAggregationBuilder->build(
                $bucket,
                $request->getDimensions(),
                $queryResult,
                $dataProvider
            );
        }

        $this->query = null;

        return $aggregations;
    }

    /**
     * Sets the QueryContainer instance to the internal property in order to use it in build process
     *
     * @param QueryContainer $query
     * @return $this
     */
    public function setQuery(QueryContainer $query)
    {
        $this->query = $query;

        return $this;
    }

    private function convert($data)
    {
        /** @var Mapper $mapper */
        $mapper = $this->objectManager->create(
            \Magento\Framework\Search\Request\Mapper::class,
            [
                'objectManager' => $this->objectManager,
                'rootQueryName' => $data['query'],
                'queries' => $data['queries'],
                'aggregations' => $data['aggregations'],
                'filters' => $data['filters']
            ]
        );

        return $mapper->getBuckets();

    }

    private function processData($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->processData($value);
            } else {
                if (strpos($value, '$price_dynamic_algorithm$') !== false) {
                    if (is_string('auto')) {
                        $data[$key] = str_replace('$price_dynamic_algorithm$', 'auto', $value);
                    } else {
                        $data[$key] = 'auto';
                    }
                    $data['is_bind'] = true;
                }
            }
        }
        return $data;
    }

    public function clean($data)
    {
        foreach ($data as $aggregationName => $aggregationValue) {
            switch ($aggregationValue['type']) {
                case BucketInterface::TYPE_TERM:
                    foreach ($aggregationValue['parameter'] ?? [] as $key => $parameter) {
                        if (is_string($parameter['value'])
                            && preg_match('/^\$(.+)\$$/si', $parameter['value'])
                        ) {
                            unset($data[$aggregationName]['parameter'][$key]);
                        }
                    }
                    break;
                case BucketInterface::TYPE_DYNAMIC:
                    if (is_string($aggregationValue['method'])
                        && preg_match('/^\$(.+)\$$/si', $aggregationValue['method'])
                    ) {
                        unset($data[$aggregationName]);
                    }
            }
        }
        return $data;
    }

}
