<?php

namespace Recomdoai\Catalog\Model;

use Magento\Catalog\Model\ResourceModel\ConfigFactory;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Store\Model\StoreManagerInterface;
use Recomdoai\Core\Helper\Connection;
use Magento\Framework\App\Request\Http;

class Config extends \Magento\Catalog\Model\Config
{
    public function __construct(
        CacheInterface                                                            $cache,
        TypeFactory                                                               $entityTypeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory            $entityTypeCollectionFactory,
        StateInterface                                                            $cacheState,
        UniversalFactory                                                          $universalFactory,
        ScopeConfigInterface                                                      $scopeConfig,
        ConfigFactory                                                             $configFactory,
        \Magento\Catalog\Model\Product\TypeFactory                                $productTypeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory   $setCollectionFactory,
        StoreManagerInterface                                                     $storeManager, \Magento\Eav\Model\Config $eavConfig,
        Connection                                                                $connectionhelper,
        CatalogSession                                                            $catalogSession,
        Http                                                                      $request,
        SerializerInterface                                                       $serializer = null,
                                                                                  $attributesForPreload = [])
    {
        $this->catalogSession = $catalogSession;
        $this->connecthelper = $connectionhelper;
        $this->request = $request;
        parent::__construct($cache, $entityTypeFactory, $entityTypeCollectionFactory, $cacheState, $universalFactory, $scopeConfig, $configFactory, $productTypeFactory, $groupCollectionFactory, $setCollectionFactory, $storeManager, $eavConfig, $serializer, $attributesForPreload);
    }

    public function getAttributeUsedForSortByArray()
    {
        $options = ['position' => __('Position')];

        if (!empty($this->getRecomdoAISortOptions())) {
            foreach ($this->getRecomdoAISortOptions() as $attribute) {
                $options[$attribute['code']] = $attribute['Name'];
            }
        } else {
            foreach ($this->getAttributesUsedForSortBy() as $attribute) {
                $options[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
            }
        }

        return $options;
    }

    public function getRecomdoAISortOptions()
    {
        try {
            if (!$this->isSearchPage()) {
                $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->_storeManager->getStore()->getCode() . '/sort_by_options/?Category-Id=' . urlencode($this->request->getParam('id')));
            } else {
                $rawResponse = $this->connecthelper->requestGetAPI('search/recomdoai_api/rest/' . $this->_storeManager->getStore()->getCode() . '/sort_by_options/?Category-Id=' . urlencode('SEARCH_PAGE'));
            }
            if (!isset($rawResponse['data']) || empty($rawResponse['data'])) {
                $rawResponse['data'] = [];
            }


        } catch (\Exception $e) {
            // return empty search result in case an exception is thrown from OpenSearch
            $rawResponse['data'] = [];
        }
        return $rawResponse['data'];
    }

    public function isSearchPage()
    {
        return strpos($this->request->getPathInfo(), '/catalogsearch/') === 0;
    }
}
