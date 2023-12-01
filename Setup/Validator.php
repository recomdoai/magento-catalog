<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Recomdoai\Catalog\Setup;

use Magento\Search\Model\SearchEngine\ValidatorInterface;
use Recomdoai\Core\Helper\Connection;

/**
 * Validate Search engine connection
 */
class Validator implements ValidatorInterface
{

    const URL_CONNECT_BEGIN = 'search/recomdoai_api/check_auth';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function validate(): array
    {
        $errors = [];
        try {
            $responseData = $this->connection->requestGetAPI(self::URL_CONNECT_BEGIN);
            if (!isset($responseData['data']) && $responseData['data']['_id'] == '') {
                $errors[] = "Could not validate a connection to the Search engine: Recomdoai Search";
            }

        } catch (\Exception $e) {
            $errors[] = 'Could not validate a connection to the Recomdoai Search. ' . $e->getMessage();
        }
        return $errors;
    }
}
