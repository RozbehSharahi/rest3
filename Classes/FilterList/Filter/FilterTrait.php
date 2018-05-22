<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

trait FilterTrait
{
    /**
     * @param QueryBuilder $query
     * @return string
     */
    protected function getMainAlias(QueryBuilder $query): string
    {
        return trim($query->getQueryPart('from')[0]['alias'], '`');
    }

    /**
     * @param QueryBuilder $query
     * @param string $tableName
     * @return bool
     */
    protected function hasJoin(QueryBuilder $query, string $tableName): bool
    {
        foreach (reset($query->getQueryPart('join')) as $join) {
            if (trim($join['joinTable'], '`') === $tableName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $items
     * @param $counts
     * @return mixed
     */
    protected function populateCounts($items, $counts)
    {
        foreach ($items as $index => $item) {
            $countsFound = array_filter($counts, function ($count) use ($item) {
                return $item['identification'] === $count['identification'];
            });
            $count = reset($countsFound);
            $items[$index]['count'] = $count['count'] ?: 0;
        }
        return $items;
    }

}
