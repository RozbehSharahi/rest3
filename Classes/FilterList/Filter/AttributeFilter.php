<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

class AttributeFilter implements FilterInterface
{

    use FilterTrait;

    /**
     * @var array
     */
    protected $configurationProperties = ['propertyName'];

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @param QueryBuilder $query
     * @param array $values
     * @return QueryBuilder Will be used to continue query filtering
     */
    public function addFilter(QueryBuilder $query, array $values): QueryBuilder
    {
        if (empty($values)) {
            return $query;
        }

        return $query->andWhere(
            $query->expr()->in($this->getMainAlias($query) . '.' . $this->propertyName, $this->escapeValues($values))
        );
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $baseQuery
     * @param array $values
     * @return array
     */
    public function getFilterItems(QueryBuilder $query, QueryBuilder $baseQuery, array $values): array
    {
        return $this->populateCounts(
            $this->getItems(clone $baseQuery),
            $this->getCounts(clone $query)
        );
    }

    /**
     * @param QueryBuilder $baseQuery
     * @return array
     */
    protected function getItems(QueryBuilder $baseQuery): array
    {
        $mainAlias = $this->getMainAlias($baseQuery);
        $baseQuery->select([
            "$mainAlias.$this->propertyName as identification",
            "$mainAlias.$this->propertyName as label"
        ]);
        $baseQuery->groupBy("identification");
        return $baseQuery->execute()->fetchAll();
    }

    /**
     * @param QueryBuilder $query
     * @return array
     */
    protected function getCounts(QueryBuilder $query): array
    {
        $mainAlias = $this->getMainAlias($query);
        $mainTable = $this->getMainTable($query);
        return (clone $query)
            ->resetQueryParts()
            ->from($mainTable, 'counter')
            ->select(["counter.$this->propertyName as identification", "count(*) as count"])
            ->where($query->expr()->in("counter.uid", $query->select(["$mainAlias.uid"])->getSQL()))
            ->groupBy(["identification"])
            ->execute()
            ->fetchAll();
    }

}
