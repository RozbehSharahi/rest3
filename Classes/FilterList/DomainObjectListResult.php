<?php

namespace RozbehSharahi\Rest3\FilterList;

class DomainObjectListResult implements FilterListResultInterface
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $filterItems;

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): FilterListResultInterface
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilterItems(): array
    {
        return $this->filterItems;
    }

    /**
     * @param array $filterItems
     * @return FilterListResultInterface
     */
    public function setFilterItems(array $filterItems): FilterListResultInterface
    {
        $this->filterItems = $filterItems;
        return $this;
    }
}
