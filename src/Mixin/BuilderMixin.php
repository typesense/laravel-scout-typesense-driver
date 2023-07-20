<?php

namespace Typesense\LaravelTypesense\Mixin;

use Closure;

/**
 * Class BuilderMixin.
 *
 * @mixin \Laravel\Scout\Builder
 *
 * @date 09/10/2021
 *
 * @author Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class BuilderMixin
{
    /**
     * @return \Closure
     */
    public function count(): Closure
    {
        return function () {
            return $this->engine()
                        ->getTotalCount($this->engine()
                                             ->search($this));
        };
    }

    /**
     * @param string $column
     * @param float  $lat
     * @param float  $lng
     * @param string $direction
     *
     * @return \Closure
     */
    public function orderByLocation(): Closure
    {
        return function (string $column, float $lat, float $lng, string $direction = 'asc') {
            $this->engine()
                 ->orderByLocation($column, $lat, $lng, $direction);

            return $this;
        };
    }

    /**
     * @param array|string $groupBy
     *
     * @return \Closure
     */
    public function groupBy(): Closure
    {
        return function (array|string $groupBy) {
            $groupBy = is_array($groupBy) ? $groupBy : func_get_args();
            $this->engine()
                 ->groupBy($groupBy);

            return $this;
        };
    }

    /**
     * @param int $groupByLimit
     *
     * @return \Closure
     */
    public function groupByLimit(): Closure
    {
        return function (int $groupByLimit) {
            $this->engine()
                 ->groupByLimit($groupByLimit);

            return $this;
        };
    }

    /**
     * @param string $startTag
     *
     * @return \Closure
     */
    public function setHighlightStartTag(): Closure
    {
        return function (string $startTag) {
            $this->engine()
                 ->setHighlightStartTag($startTag);

            return $this;
        };
    }

    /**
     * @param string $endTag
     *
     * @return \Closure
     */
    public function setHighlightEndTag(): Closure
    {
        return function (string $endTag) {
            $this->engine()
                 ->setHighlightEndTag($endTag);

            return $this;
        };
    }

    /**
     * @param int $limitHits
     *
     * @return \Closure
     */
    public function limitHits(): Closure
    {
        return function (int $limitHits) {
            $this->engine()
                 ->limitHits($limitHits);

            return $this;
        };
    }

    /**
     * @param array $facetBy
     *
     * @return \Closure
     */
    public function facetBy(): Closure
    {
        return function (array $facetBy) {
            $this->engine()
                ->facetBy($facetBy);

            return $this;
        };
    }

    /**
     * @param int $maxFacetValues
     *
     * @return \Closure
     */
    public function setMaxFacetValues(): Closure
    {
        return function (int $maxFacetValues) {
            $this->engine()
                ->setMaxFacetValues($maxFacetValues);

            return $this;
        };
    }

    /**
     * @param string $facetQuery
     *
     * @return \Closure
     */
    public function facetQuery(): Closure
    {
        return function (string $facetQuery) {
            $this->engine()
                ->facetQuery($facetQuery);

            return $this;
        };
    }

    /**
     * @param array $includeFields
     *
     * @return \Closure
     */
    public function setIncludeFields(): Closure
    {
        return function (array $includeFields) {
            $this->engine()
                ->setIncludeFields($includeFields);

            return $this;
        };
    }

    /**
     * @param array $excludeFields
     *
     * @return \Closure
     */
    public function setExcludeFields(): Closure
    {
        return function (array $excludeFields) {
            $this->engine()
                ->setExcludeFields($excludeFields);

            return $this;
        };
    }

    /**
     * @param array $highlightFields
     *
     * @return \Closure
     */
    public function setHighlightFields(): Closure
    {
        return function (array $highlightFields) {
            $this->engine()
                ->setHighlightFields($highlightFields);

            return $this;
        };
    }

    /**
     * @param array $pinnedHits
     *
     * @return \Closure
     */
    public function setPinnedHits(): Closure
    {
        return function (array $pinnedHits) {
            $this->engine()
                ->setPinnedHits($pinnedHits);

            return $this;
        };
    }

    /**
     * @param array $hiddenHits
     *
     * @return \Closure
     */
    public function setHiddenHits(): Closure
    {
        return function (array $hiddenHits) {
            $this->engine()
                ->setHiddenHits($hiddenHits);

            return $this;
        };
    }

    /**
     * @param array $highlightFullFields
     *
     * @return \Closure
     */
    public function setHighlightFullFields(): Closure
    {
        return function (array $highlightFullFields) {
            $this->engine()
                ->setHighlightFullFields($highlightFullFields);

            return $this;
        };
    }

    /**
     * @param int $highlightAffixNumTokens
     *
     * @return \Closure
     */
    public function setHighlightAffixNumTokens(): Closure
    {
        return function (int $highlightAffixNumTokens) {
            $this->engine()
                ->setHighlightAffixNumTokens($highlightAffixNumTokens);

            return $this;
        };
    }

    /**
     * @param string $infix
     *
     * @return \Closure
     */
    public function setInfix(): Closure
    {
        return function (string $infix) {
            $this->engine()
                ->setInfix($infix);

            return $this;
        };
    }

    /**
     * @param int $snippetThreshold
     *
     * @return \Closure
     */
    public function setSnippetThreshold(): Closure
    {
        return function (int $snippetThreshold) {
            $this->engine()
                ->setSnippetThreshold($snippetThreshold);

            return $this;
        };
    }

    /**
     * @param bool $exhaustiveSearch
     *
     * @return \Closure
     */
    public function exhaustiveSearch(): Closure
    {
        return function (bool $exhaustiveSearch) {
            $this->engine()
                ->exhaustiveSearch($exhaustiveSearch);

            return $this;
        };
    }

    /**
     * @param bool $useCache
     *
     * @return \Closure
     */
    public function setUseCache(): Closure
    {
        return function (bool $useCache) {
            $this->engine()
                ->setUseCache($useCache);

            return $this;
        };
    }

    /**
     * @param int $cacheTtl
     *
     * @return \Closure
     */
    public function setCacheTtl(): Closure
    {
        return function (int $cacheTtl) {
            $this->engine()
                ->setCacheTtl($cacheTtl);

            return $this;
        };
    }

    /**
     * @param bool $prioritizeExactMatch
     *
     * @return \Closure
     */
    public function setPrioritizeExactMatch(): Closure
    {
        return function (bool $prioritizeExactMatch) {
            $this->engine()
                ->setPrioritizeExactMatch($prioritizeExactMatch);

            return $this;
        };
    }

    /**
     * @param bool $enableOverrides
     *
     * @return \Closure
     */
    public function enableOverrides(): Closure
    {
        return function (bool $enableOverrides) {
            $this->engine()
                ->enableOverrides($enableOverrides);

            return $this;
        };
    }

    /**
     * @param array $searchRequests
     *
     * @return \Closure
     */
    public function searchMulti(): Closure
    {
        return function (array $searchRequests) {
            $this->engine()->searchMulti($searchRequests);

            return $this;
        };
    }
}
