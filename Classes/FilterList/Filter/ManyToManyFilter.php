<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;

class ManyToManyFilter implements FilterInterface, JsonApiFilterInterface
{

    use FilterTrait;

    /**
     * @var array
     */
    protected $configurationProperties = [
        'propertyName',
        'foreignTable',
        'foreignLabel',
        'relationTable',
        'relationTableLocalField',
        'relationTableForeignField'
    ];

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
     * @var string
     */
    protected $relationTable;

    /**
     * @var
     */
    protected $relationTableLocalField;

    /**
     * @var
     */
    protected $relationTableForeignField;

    /**
     * @param QueryBuilder $query
     * @param array $values
     * @param string $name
     * @return QueryBuilder Will be used to continue query filtering
     */
    public function addFilter(QueryBuilder $query, array $values, string $name): QueryBuilder
    {
        if (empty($values)) {
            return $query;
        }

        $mainAlias = $this->getMainAlias($query);

        if (!$this->hasJoin($query, $this->relationTable)) {
            $query->leftJoin(
                "`$mainAlias`",
                $this->relationTable,
                $this->relationTable,
                "$mainAlias.uid=$this->relationTable.$this->relationTableLocalField"
            );
        }

        $query->andWhere(
            $query->expr()->in("$this->relationTable.$this->relationTableForeignField", $this->escapeValues($values))
        );
        return $query;
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $baseQuery
     * @param array $values
     * @param string $name
     * @return array
     */
    public function getFilterItems(QueryBuilder $query, QueryBuilder $baseQuery, array $values, string $name): array
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
            ->from($mainTable, "counter")
            ->select(["counter_foreign.uid as identification", "counter_foreign.$this->foreignLabel as label"])
            ->innerJoin(
                "counter",
                $this->relationTable,
                "counter_relation",
                "counter.uid=counter_relation.$this->relationTableLocalField"
            )
            ->innerJoin(
                "counter_relation",
                $this->foreignTable,
                "counter_foreign",
                "counter_relation.$this->relationTableForeignField=counter_foreign.uid"
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
            ->from($mainTable, "counter")
            ->select(["counter_foreign.uid as identification", "count(*) as count"])
            ->innerJoin(
                "counter",
                $this->relationTable,
                "counter_relation",
                "counter.uid=counter_relation.$this->relationTableLocalField"
            )
            ->innerJoin(
                "counter_relation",
                $this->foreignTable,
                "counter_foreign",
                "counter_relation.$this->relationTableForeignField=counter_foreign.uid"
            )
            ->where($query->expr()->in("counter.uid", $query->select(["$mainAlias.uid"])->getSQL()))
            ->groupBy(["identification"])
            ->execute()
            ->fetchAll();
    }

    /**
     * This will be merged into the filter item
     *
     * @param ServerRequestInterface $request
     * @param array $filterItems
     * @param array $values
     * @param string $name
     * @return array
     */
    public function getMeta(ServerRequestInterface $request, array $filterItems, array $values, string $name): array
    {
        return $this->populateFilterItemWithFilterSelectors($request, $filterItems, $name);
    }
}
