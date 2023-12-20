<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\Setup;

use Magento\Search\Model\SearchEngine\ValidatorInterface;
use Recomdoai\Core\Helper\Connection;
use Recomdoai\Core\Model\ResourceModel\RecomdoConnect\Collection as RecomdoConnectCollection;

/**
 * Validate Search engine connection
 */
class Validator implements ValidatorInterface
{

    const URL_CONNECT_BEGIN = 'search/recomdoai_api/check_auth';

    public function __construct(Connection $connection, Connection $connection_helper, RecomdoConnectCollection $recomdoconnectdetailsCollection,)
    {
        $this->connection_helper = $connection_helper;
        $this->recomdoconnectdetailsCollection = $recomdoconnectdetailsCollection;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function validate(): array
    {
        $errors = [];
        try {
            $client_data = $this->connection_helper->getAuthDetails();
            $this->recomdoconnectdetailsCollection->addFieldToFilter('client_key', $client_data['client_key']);
            $item = $this->recomdoconnectdetailsCollection->getFirstItem();
            if ($item->getId() !== null && $item->getStatus() == 1) {
                $responseData = $this->connection->requestGetAPI(self::URL_CONNECT_BEGIN);
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
