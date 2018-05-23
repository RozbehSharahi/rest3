<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

class ManyToOneFilter implements FilterInterface
{

    use FilterTrait;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $foreignTable;

    /**
     * @var string
     */
    protected $foreignLabel;

    /**
     * DomainObjectHasOneFilter constructor.
     * @param string $propertyName
     * @param string $foreignTable
     * @param string $foreignLabel
     */
    public function __construct(string $propertyName, string $foreignTable, string $foreignLabel)
    {
        $this->propertyName = $propertyName;
        $this->foreignTable = $foreignTable;
        $this->foreignLabel = $foreignLabel;
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

        $query->andWhere($query->expr()->in($this->propertyName, $values));
        return $query;
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
     * @param QueryBuilder $query
     * @return array
     */
    protected function getItems(QueryBuilder $query): array
    {
        $mainAlias = $this->getMainAlias($query);
        $mainTable = $this->getMainTable($query);
        return (clone $query)
            ->resetQueryParts()
            ->from($mainTable, 'counter')
            ->select(["counter.$this->propertyName as identification", "counter_foreign.$this->foreignLabel as label"])
            ->innerJoin(
                "counter",
                $this->foreignTable,
                "counter_foreign",
                "counter.$this->propertyName=counter_foreign.uid"
            )
            ->where($query->expr()->in("counter.uid", $query->select(["$mainAlias.uid"])->getSQL()))
            ->groupBy(["identification"])
            ->execute()
            ->fetchAll();
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
            ->innerJoin(
                "counter",
                $this->foreignTable,
                "counter_foreign",
                "counter.$this->propertyName=counter_foreign.uid"
            )
            ->where($query->expr()->in("counter.uid", $query->select(["$mainAlias.uid"])->getSQL()))
            ->groupBy(["identification"])
            ->execute()
            ->fetchAll();
    }

}
