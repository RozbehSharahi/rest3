<?php

namespace RozbehSharahi\Rest3\FilterList;

use RozbehSharahi\Rest3\FilterList\Filter\FilterInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class FilterList implements FilterListInterface
{

    /**
     * @var FilterInterface[]
     */
    protected $filterSet;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $pageSize = 10;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder
     */
    protected $baseQuery;

    /**
     * @var Typo3DbQueryParser
     */
    protected $queryParser;

    /**
     * @param Typo3DbQueryParser $queryParser
     */
    public function injectQueryParser(Typo3DbQueryParser $queryParser)
    {
        $this->queryParser = $queryParser;
    }

    /**
     * DomainObjectList constructor.
     * @param array $filterSet
     */
    public function __construct(array $filterSet = [])
    {
        $this->filterSet = $filterSet;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(): \Doctrine\DBAL\Query\QueryBuilder
    {
        $query = $this->createFilterQuery();
        $mainAlias = $query->getQueryPart('from')['0']['alias'];
        $filteredQuery = $query->select(["$mainAlias.uid"])->groupBy(["$mainAlias.uid"]);
        return $filteredQuery;
    }

    /**
     * @return array
     */
    public function getFilterItems(): array
    {
        $filterItems = [];
        foreach ($this->filterSet as $index => $filterSet) {
            $filterItems[$index] = $filterSet->getFilterItems(
                $this->createFilterQuery([$index]),
                clone $this->baseQuery,
                $this->filters[$index] ?: []
            );
        }
        return $filterItems;
    }

    /**
     * @param array $excludedFilters
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    protected function createFilterQuery(array $excludedFilters = []): \Doctrine\DBAL\Query\QueryBuilder
    {
        $query = clone $this->baseQuery;
        foreach (array_filter($this->filters) as $index => $values) {
            if (is_null($this->filterSet[$index])) {
                throw new \Exception("`$index` does not exist in filter set");
            }
            if (!in_array($index, $excludedFilters)) {
                $query = $this->filterSet[$index]->addFilter($query, $values);
            }
        }
        return $query;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        return [
            'items' => $this->getQuery()->execute()->fetchAll(),
            'filterItems' => $this->getFilterItems()
        ];
    }

    /**
     * @param $baseQuery
     * @return FilterListInterface
     * @throws \Exception
     */
    public function setBaseQuery($baseQuery): FilterListInterface
    {
        if ($baseQuery instanceof QueryInterface) {
            $this->baseQuery = clone $this->queryParser
                ->convertQueryToDoctrineQueryBuilder($baseQuery)
                ->getConcreteQueryBuilder();
            return $this;
        }

        if ($baseQuery instanceof QueryBuilder) {
            $this->baseQuery = clone $baseQuery->getConcreteQueryBuilder();
            return $this;
        }

        if ($baseQuery instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            $this->baseQuery = clone $baseQuery;
            return $this;
        }

        throw new \Exception('Could not use base query given to ' . __METHOD__);
    }

    /**
     * @return FilterListInterface
     */
    public function resetSettings(): FilterListInterface
    {
        $this->baseQuery = null;
        $this->filters = [];
        return $this;
    }

    /**
     * @param array $filterSet
     * @return FilterListInterface
     */
    public function setFilterSet(array $filterSet): FilterListInterface
    {
        $this->filterSet = $filterSet;
    }

    /**
     * @return array
     */
    public function getFilterSet(): array
    {
        return $this->filterSet;
    }

    /**
     * @param array $filters
     * @return FilterListInterface
     */
    public function setFilters(array $filters): FilterListInterface
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

}
