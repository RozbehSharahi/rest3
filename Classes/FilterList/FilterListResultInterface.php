<?php

namespace RozbehSharahi\Rest3\FilterList;

interface FilterListResultInterface
{

    /**
     * @return array
     */
    public function getItems(): array;

    /**
     * @param array $items
     * @return FilterListResultInterface
     */
    public function setItems(array $items): FilterListResultInterface;

    /**
     * @return array
     */
    public function getFilterItems(): array;

    /**
     * @param array $filterItems
     * @return FilterListResultInterface
     */
    public function setFilterItems(array $filterItems): FilterListResultInterface;

}
