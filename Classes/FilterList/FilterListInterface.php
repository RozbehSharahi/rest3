<?php

namespace RozbehSharahi\Rest3\FilterList;

use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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
     * @param QueryBuilder|QueryInterface|\TYPO3\CMS\Core\Database\Query\QueryBuilder $baseQuery
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
