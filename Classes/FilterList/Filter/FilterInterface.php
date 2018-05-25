<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

interface FilterInterface
{

    /**
     * @param array $configuration
     * @return FilterInterface
     */
    public function setConfiguration(array $configuration): FilterInterface;

    /**
     * @param QueryBuilder $query
     * @param array $values
     * @param string $name
     * @return QueryBuilder Will be used to continue query filtering
     */
    public function addFilter(QueryBuilder $query, array $values, string $name): QueryBuilder;

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $baseQuery
     * @param array $values
     * @param string $name
     * @return array
     */
    public function getFilterItems(QueryBuilder $query, QueryBuilder $baseQuery, array $values, string $name): array;

}
