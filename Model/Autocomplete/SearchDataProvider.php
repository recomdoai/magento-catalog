<?php

namespace Recomdoai\Catalog\Model\Autocomplete;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Category\Attribute\Source\Layout;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Recomdoai\Core\Helper\Connection;


/**
 * Full text search implementation of autocomplete.
 */
class SearchDataProvider extends \Magento\CatalogSearch\Model\Autocomplete\DataProvider
{
    /**
     * Price currency
     *
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    protected $_layerResolver;

    /**
     * Layout
     *
     * @var Layout
     */
    protected $_layout;

    /**
     * Catalog Product collection
     *
     * @var Collection
     */
    protected $_productCollection;

    /**
     * Image helper
     *
     * @var Image
     */
    protected $_imageHelper;

    /**
     * Retrieve loaded product collection
     *
     * @return Collection
     */

    /**
     * @param Context $context
     * @param QueryFactory $queryFactory
     * @param ItemFactory $itemFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param LayoutInterface $layout
     * @param Resolver $layerResolver
     * @return void
     */

    public function __construct
    (
        QueryFactory           $queryFactory,
        ItemFactory            $itemFactory,
        ScopeConfig            $scopeConfig,
        Context                $context,
        PriceCurrencyInterface $priceCurrency,
        Resolver               $layerResolver,
        StoreManagerInterface  $storeManager,
        Connection             $connectionhelper
    )
    {
        $this->_priceCurrency = $priceCurrency;
        $this->_layout = $context->getLayout();
        $this->_layerResolver = $layerResolver;
        $this->_imageHelper = $context->getImageHelper();
        $this->storeManager = $storeManager;
        $this->connecthelper = $connectionhelper;
        parent::__construct($queryFactory, $itemFactory, $scopeConfig);
    }


    protected function _getProductCollection()
    {
        if (null === $this->_productCollection) {
            $this->_productCollection = $this->_layout->getBlock('search_result_list')->getLoadedProductCollection();
        }
        return $this->_productCollection;
    }

    private function getCategorySuggestions($query)
    {
        $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->storeManager->getStore()->getCode() . '/category_search/?searchCriteria=' . $query);

        if (!isset($rawResponse['data']) || empty($rawResponse['data'])) {
            $rawResponse['data'] = [];
        }

        return $rawResponse['data'];
    }

    /**
     * Get product price
     *
     * @param Product $product
     * @return string
     */
    protected function _getProductPrice($product)
    {
        return $this->_priceCurrency->format($product->getFinalPrice($product), false, PriceCurrencyInterface::DEFAULT_PRECISION, $product->getStore());
    }

    /**
     * Get product reviews
     *
     * @param Product $product
     * @return string
     */
    protected function _getProductReviews($product)
    {
        return $this->_layout->createBlock('Magento\Review\Block\View')
            ->getReviewsSummaryHtml($product, 'short');
    }

    /**
     * Product image url getter
     *
     * @param Product $product
     * @return string
     */
    protected function _getImageUrl($product)
    {
        return $this->_imageHelper->init($product, 'product_page_image_small')->getUrl();
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        $collection = $this->_getProductCollection();
        $suggetionCategory = $this->getCategorySuggestions($this->queryFactory->get()->getQueryText());

        $results = [];
        foreach ($collection as $product) {
            /** @var \Magento\Catalog\Model\Product $product */
            $results['products'][$product->getId()] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $this->_getProductPrice($product),
                'reviews' => $this->_getProductReviews($product),
                'image' => $this->_getImageUrl($product),
                'url' => $product->getProductUrl(),
            ];
        }
        $results['categories'] = $suggetionCategory;
        return $results;
    }
}
