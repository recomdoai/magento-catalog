<?php

declare(strict_types=1);

namespace Recomdoai\Catalog\SearchAdapter;

use Magento\Framework\Search\RequestInterface;

class Mapper
{

    private $mapper;

    public function __construct(\Recomdoai\Catalog\Elasticsearch5\SearchAdapter\Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function buildQuery(RequestInterface $request): array
    {
        $searchQuery = $this->mapper->buildQuery($request);
        return $searchQuery;
    }
}
