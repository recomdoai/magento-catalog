<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Recomdoai\Catalog\SearchAdapter\Query\Builder;

use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;

/**
 * @api
 * @since 100.1.0
 */
interface QueryInterface
{
    /**
     * @param array $selectQuery
     * @param RequestQueryInterface $requestQuery
     * @param string $conditionType
     * @return array
     * @since 100.1.0
     */
    public function build(
        array $selectQuery,
        RequestQueryInterface $requestQuery,
        $conditionType
    );
}
