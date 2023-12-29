<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Recomdoai\Catalog\Model\Layer\Search;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Recomdoai\Core\Helper\Connection;

class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Search\FilterableAttributeList
{

    public function __construct(
        CollectionFactory     $collectionFactory,
        StoreManagerInterface $storeManager,
        Connection            $connectionhelper,
    )
    {
        $this->connecthelper = $connectionhelper;
        parent::__construct($collectionFactory, $storeManager);
    }

    protected function _prepareAttributeCollection($collection)
    {
        $collection->addIsFilterableFilter();

        $customOrder = $this->getRecomdoAIAttributes();

        if (!empty($customOrder)) {

            $mapping = [];
            foreach ($customOrder as $attribute) {
                $mapping[$attribute['code']] = $attribute['weight'];
            }

            // Custom sorting function
            usort($customOrder, function ($a, $b) use ($mapping) {
                $weightA = $mapping[$a['code']] ?? PHP_INT_MAX;
                $weightB = $mapping[$b['code']] ?? PHP_INT_MAX;

                return $weightA <=> $weightB;
            });

            // Add custom sorting logic based on the predefined order
            $orderExpression = new \Zend_Db_Expr("CASE WHEN attribute_code IN ('" . implode("','", array_keys($mapping)) . "') THEN 0 ELSE 1 END, FIELD(attribute_code, '" . implode("','", array_keys($mapping)) . "')");

            $collection->getSelect()->order($orderExpression);
        }

        return $collection;
    }

    public function getRecomdoAIAttributes()
    {
        try {
            $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->storeManager->getStore()->getCode() . '/layered_navigation_filter/?Category-Id=' . urlencode("SEARCH_PAGE"));

            if (!isset($rawResponse['data']) || empty($rawResponse['data'])) {
                $rawResponse['data'] = [];
            }

        } catch (\Exception $e) {
            // return empty search result in case an exception is thrown from OpenSearch
            $rawResponse['data'] = [];
        }
        return $rawResponse['data'];
    }
}
