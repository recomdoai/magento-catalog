<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Recomdoai\Catalog\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;

/**
 * ProductsIndexer Handler for Elasticsearch engine.
 */
class IndexerHandler implements IndexerInterface
{

    /**
     * @inheritdoc
     */
    public function isAvailable($dimensions = [])
    {
        return false;
    }

    public function saveIndex($dimensions, \Traversable $documents)
    {
        // TODO: Implement saveIndex() method.
    }

    public function deleteIndex($dimensions, \Traversable $documents)
    {
        // TODO: Implement deleteIndex() method.
    }

    public function cleanIndex($dimensions)
    {
        // TODO: Implement cleanIndex() method.
    }
}
