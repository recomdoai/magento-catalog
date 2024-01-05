<?php

declare(strict_types=1);

namespace Recomdoai\Catalog\Model\DataProvider;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Magento\AdvancedSearch\Model\SuggestedQueriesInterface;
use Magento\Elasticsearch\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Recomdoai\Catalog\Model\DataProvider\Base\GetSuggestionFrequencyInterface;
use Recomdoai\Core\Helper\Connection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\QueryInterface;
use Magento\Search\Model\QueryResultFactory;
use Magento\Store\Model\ScopeInterface;

class Suggestions implements SuggestedQueriesInterface
{

    private $responseErrorExceptionList = [
        'elasticsearchBadRequest404' => BadRequest400Exception::class
    ];

    public function __construct(
        ScopeConfigInterface             $scopeConfig,
        Config                           $config,
        QueryResultFactory               $queryResultFactory,
        Connection                       $connectionhelper,
        LoggerInterface                  $logger = null,
        ?GetSuggestionFrequencyInterface $getSuggestionFrequency = null,
        StoreManagerInterface            $storeManager,
        array                            $responseErrorExceptionList = []
    )
    {
        $this->connecthelper = $connectionhelper;
        $this->queryResultFactory = $queryResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->logger = $logger;
        $this->getSuggestionFrequency = $getSuggestionFrequency;
        $this->storeManager = $storeManager;
        $this->responseErrorExceptionList = array_merge($this->responseErrorExceptionList, $responseErrorExceptionList);
    }

    private function getSuggestions(QueryInterface $query)
    {
        $searchSuggestionsCount = 20;
        $suggestions = [];
        $queryText = urlencode($query->getQueryText());

        $result = $this->connecthelper->requestGetAPI('search/recomdoai_api/' . $this->storeManager->getStore()->getCode() . '/suggestions?keyword=' . $queryText);

        if (is_array($result) && isset($result['data'])) {
            foreach ($result['data']['suggest'] ?? [] as $suggest) {
                foreach ($suggest as $token) {
                    foreach ($token['options'] ?? [] as $key => $suggestion) {
                        $suggestions[$suggestion['score'] . '_' . $key] = $suggestion;
                    }
                }
            }
            krsort($suggestions);
            $texts = array_unique(array_column($suggestions, 'text'));
            $suggestions = array_slice(
                array_intersect_key(array_values($suggestions), $texts),
                0,
                $searchSuggestionsCount
            );
        }

        return $suggestions;
    }

    public function getItems(QueryInterface $query)
    {
        $result = [];
        if ($this->isSuggestionsAllowed()) {
            $isResultsCountEnabled = $this->isResultsCountEnabled();
            try {
                $suggestions = $this->getSuggestions($query);
            } catch (Exception $e) {
                if ($this->validateException($e)) {
                    $this->logger->critical($e);
                    $suggestions = [];
                } else {
                    throw $e;
                }
            }

            foreach ($suggestions as $suggestion) {
                $count = null;
                if ($isResultsCountEnabled) {
                    try {
                        $count = $this->getSuggestionFrequency->execute($suggestion['text']);
                    } catch (Exception $e) {
                        $this->logger->critical($e);
                    }

                }
                $result[] = $this->queryResultFactory->create(
                    [
                        'queryText' => $suggestion['text'],
                        'resultsCount' => $count,
                    ]
                );
            }
        }

        return $result;
    }

    public function isResultsCountEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            SuggestedQueriesInterface::SEARCH_SUGGESTION_COUNT_RESULTS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function validateException(Exception $exception): bool
    {
        return in_array(get_class($exception), $this->responseErrorExceptionList, true);
    }

    private function getSearchSuggestionsCount()
    {
        return (int)$this->scopeConfig->getValue(
            SuggestedQueriesInterface::SEARCH_SUGGESTION_COUNT,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function isSuggestionsAllowed()
    {
        $isSuggestionsEnabled = $this->scopeConfig->isSetFlag(
            SuggestedQueriesInterface::SEARCH_SUGGESTION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $isEnabled = $this->config->isElasticsearchEnabled();

        return $isEnabled && $isSuggestionsEnabled;
    }
}
