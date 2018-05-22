<?php

namespace RozbehSharahi\Rest3\FilterList;

interface FilterListInterface
{

    public function resetSettings(): FilterListInterface;

    /**
     * @param mixed $baseQuery
     * @return FilterListInterface
     */
    public function setBaseQuery($baseQuery): FilterListInterface;

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

    /**
     * @param int $page
     * @return FilterListInterface
     */
    public function setPage(int $page): FilterListInterface;

    /**
     * @return int
     */
    public function getPage(): int;

    /**
     * @param int $pageSize
     * @return FilterListInterface
     */
    public function setPageSize(int $pageSize): FilterListInterface;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @return FilterListResultInterface
     */
    public function execute(): FilterListResultInterface;

}
