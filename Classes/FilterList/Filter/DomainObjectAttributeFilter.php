<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

class DomainObjectAttributeFilter implements FilterInterface
{

    use FilterTrait;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * DomainObjectAttributeFilter constructor.
     * @param string $propertyName
     */
    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
    }

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

        return $query->andWhere($query->expr()->in($this->getMainAlias($query) . '.' . $this->propertyName,
            array_map(function ($value) {
                return "'$value'";
            }, $values)));
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
            "$mainAlias.$this->propertyName as value"
        ]);
        $baseQuery->groupBy("value");
        return $baseQuery->execute()->fetchAll();
    }

    /**
     * @param QueryBuilder $query
     * @return array
     */
    protected function getCounts(QueryBuilder $query): array
    {
        $mainAlias = $this->getMainAlias($query);
        $query->select([
            "$mainAlias.$this->propertyName as identification",
            "count(*) as count"
        ]);
        $query->groupBy(['identification']);
        return $query->execute()->fetchAll();
    }

}
