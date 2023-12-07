<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Recomdoai\Catalog\SearchAdapter\Query\Builder;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ResolverInterface as TypeResolver;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Magento\Elasticsearch\Model\Config;
use Magento\Elasticsearch\SearchAdapter\Query\ValueTransformerPool;
use Magento\Framework\Search\Request\Query\BoolExpression;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;

/**
 * Builder for match query
 */
class MatchQuery implements QueryInterface
{
    /**
     * Elasticsearch condition for case when query must not appear in the matching documents.
     */
    public const QUERY_CONDITION_MUST_NOT = 'must_not';

    /**
     * @var FieldMapperInterface
     */
    private $fieldMapper;

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * @var TypeResolver
     */
    private $fieldTypeResolver;

    /**
     * @var ValueTransformerPool
     */
    private $valueTransformerPool;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param FieldMapperInterface $fieldMapper
     * @param AttributeProvider $attributeProvider
     * @param TypeResolver $fieldTypeResolver
     * @param ValueTransformerPool $valueTransformerPool
     * @param Config $config
     */
    public function __construct(
        FieldMapperInterface $fieldMapper,
        AttributeProvider    $attributeProvider,
        TypeResolver         $fieldTypeResolver,
        ValueTransformerPool $valueTransformerPool,
        Config               $config
    )
    {
        $this->fieldMapper = $fieldMapper;
        $this->attributeProvider = $attributeProvider;
        $this->fieldTypeResolver = $fieldTypeResolver;
        $this->valueTransformerPool = $valueTransformerPool;
        $this->config = $config;
    }

    /**
     * Creates valid ElasticSearch search conditions from Match queries
     *
     * The purpose of this method is to create a structure which represents valid search query
     * for a full-text search.
     * It sets search query condition, the search query itself, and sets the search query boost.
     *
     * The search query boost is an optional in the search query and therefore it will be set to 1 by default
     * if none passed with a match query.
     *
     * @param array $selectQuery
     * @param RequestQueryInterface $requestQuery
     * @param string $conditionType
     * @return array
     */
    public function build(array $selectQuery, RequestQueryInterface $requestQuery, $conditionType)
    {
        $queryValue = $this->prepareQuery($requestQuery->getValue(), $conditionType);

        $value = preg_replace('#^"(.*)"$#m', '$1', $queryValue['value'], -1, $count);

        $selectQuery['bool'][$queryValue['condition']] = ['search' => $value];

        return $selectQuery;
    }

    /**
     * Prepare query
     *
     * @param string $queryValue
     * @param string $conditionType
     * @return array
     */
    private function prepareQuery(string $queryValue, string $conditionType): array
    {
        $condition = $conditionType === BoolExpression::QUERY_CONDITION_NOT
            ? self::QUERY_CONDITION_MUST_NOT
            : $conditionType;

        return [
            'condition' => $condition,
            'value' => $queryValue,
        ];
    }

    /**
     * Check does condition support the minimum_should_match field
     *
     * @param string $condition
     * @return bool
     */
    private function isConditionSupportMinimumShouldMatch(string $condition): bool
    {
        return !in_array($condition, [
            'match_phrase_prefix',
            'match_phrase',
        ]);
    }
}
