<?php

namespace Recomdoai\Catalog\Controller\Search;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Recomdoai\Core\Helper\Connection;

class Suggest extends Action
{
    public function __construct(
        StoreManagerInterface $storeManager,
        Connection            $connectionhelper,
        Context               $context
    )
    {
        $this->storeManager = $storeManager;
        $this->connecthelper = $connectionhelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $autocompleteItems = $this->getItems($this->getRequest()->getParam('q'));
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($autocompleteItems);
        return $resultJson;
    }

    public function getItems($query)
    {
        $collection = $this->getProductCollection($query);

        $results = [];
        if (isset($collection['data']) && !empty($collection['data']['main_results'])) {
            $results['products'] = $collection['data']['main_results'];
        } else {
            $results['products'] = [];
        }
        if (isset($collection['data']) && !empty($collection['data']['category_results'])) {
            $results['categories'] = $collection['data']['category_results'];
        } else {
            $results['categories'] = [];
        }
        if (isset($collection['data']) && !empty($collection['data']['suggestions'])) {
            $results['suggestions'] = $collection['data']['suggestions'];
        } else {
            $results['suggestions'] = [];
        }

        return $results;
    }

    protected function getProductCollection($query)
    {
        $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->storeManager->getStore()->getCode() . '/autocomplete/?keyword=' .  urlencode($query));
        return $rawResponse;
    }
}
