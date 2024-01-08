<?php
namespace Recomdoai\Catalog\Block;

class Result extends \Magento\CatalogSearch\Block\Result
{
    /**
     * Override the setListOrders method
     */
    public function setListOrders()
    {
        $category = $this->catalogLayer->getCurrentCategory();
        /* @var $category \Magento\Catalog\Model\Category */
        $availableOrders = $category->getAvailableSortByOptions();
        unset($availableOrders['position']);
        $availableOrders['relevance'] = __('Relevance');

        $this->getListBlock()->setAvailableOrders(
            $availableOrders
        )->setDefaultDirection(
            'asc'
        )->setDefaultSortBy(
            'relevance'
        );

        return $this;
    }
}
