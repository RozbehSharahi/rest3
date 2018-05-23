<?php

namespace RozbehSharahi\Rest3\FilterList;

use Doctrine\DBAL\Query\QueryBuilder;

interface FilterListInterface
{

    /**
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder;

    /**
     * @return array
     */
    public function getFilterItems(): array;

    /**
     * @param mixed $baseQuery
     * @return FilterListInterface
     */
    public function setBaseQuery($baseQuery): FilterListInterface;

    /**
     * @return FilterListInterface
     */
    public function resetSettings(): FilterListInterface;

    /**
     * @param array $filterSet
     * @return FilterListInterface
     */
    public function setFilterSet(array $filterSet): FilterListInterface;

    /**
     * @return array
     */
    public function getFilterSet(): array;

    /**
     * @param array $filters
     * @return FilterListInterface
     */
    public function setFilters(array $filters): FilterListInterface;

    /**
     * @return array
     */
    public function getFilters(): array;

}
