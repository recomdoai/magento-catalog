<?php

namespace Recomdoai\Catalog\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Search extends AbstractHelper
{

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(

        ScopeConfigInterface $scopeConfig

    )
    {
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * @return mixed
     */
    public function getmodulestatus()
    {

        $valueFromConfig = $this->scopeConfig->getValue(
            'searchmodule/general/search_enable',
            ScopeInterface::SCOPE_STORE,
        );

        return $valueFromConfig;

    }

    /**
     * @return array
     */
    public function gets3bucketdetails()
    {

        $coonnect_mode = $this->scopeConfig->getValue(
            'searchmodule/search_module_config/connect_mode',
            ScopeInterface::SCOPE_STORE,
        );

        $client_id = $this->scopeConfig->getValue(
            'searchmodule/search_module_config/client_id',
            ScopeInterface::SCOPE_STORE,
        );

        $client_secret = $this->scopeConfig->getValue(
            'searchmodule/search_module_config/client_secret',
            ScopeInterface::SCOPE_STORE,
        );

        $data = [];

        $data['mode'] = $coonnect_mode;
        $data['id'] = $client_id;
        $data['secret'] = $client_secret;

        return $data;

    }

}
