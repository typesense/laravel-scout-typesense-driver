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
     * @param bool $exhaustiveSearch
     *
     * @return Closure
     */
    public function exhaustiveSearch(): Closure
    {
        return function (bool $exhaustiveSearch) {
            $this->engine()
                ->exhaustiveSearch($exhaustiveSearch);

            return $this;
        };
    }
}
