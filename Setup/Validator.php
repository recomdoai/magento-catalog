<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\Setup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\SearchEngine\ValidatorInterface;
use Magento\Store\Model\ScopeInterface;
use Recomdoai\Core\Helper\Connection;
use Recomdoai\Core\Model\ResourceModel\RecomdoConnect\Collection as RecomdoConnectCollection;

/**
 * Validate Search engine connection
 */
class Validator implements ValidatorInterface
{

    const URL_CONNECT_BEGIN = 'search/recomdoai_api/check_auth';

    public function __construct(Connection $connection_helper, RecomdoConnectCollection $recomdoconnectdetailsCollection, ScopeConfigInterface $scopeConfig,)
    {
        $this->connection_helper = $connection_helper;
        $this->recomdoconnectdetailsCollection = $recomdoconnectdetailsCollection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function validate(): array
    {
        $errors = [];
        try {
            $connection_status = $this->scopeConfig->getValue(
                'recomdoai/general/status',
                ScopeInterface::SCOPE_STORE,
            );
            if ($connection_status) {
                $responseData = $this->connection_helper->requestGetAPI(self::URL_CONNECT_BEGIN);
                if (!isset($responseData['data']) && $responseData['data']['_id'] == '') {
                    $errors[] = "Could not validate a connection to the Search engine: Recomdoai Search";
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Could not validate a connection to the Recomdoai Search. ' . $e->getMessage();
        }
        return $errors;
    }
}
