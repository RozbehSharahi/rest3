<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;

class OneToManyFilter implements FilterInterface, JsonApiFilterInterface
{

    use FilterTrait;

    /**
     * @var array
     */
    protected $configurationProperties = ['propertyName', 'foreignTable', 'foreignField', 'foreignLabel'];

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
    protected $foreignField;

    /**
     * @var string
     */
    protected $foreignLabel;

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

        $mainAlias = $this->getMainAlias($query);

        if (!$this->hasJoin($query, $this->foreignTable)) {
            $query->leftJoin(
                $mainAlias,
                $this->foreignTable,
                $this->foreignTable,
                "$mainAlias.uid=$this->foreignTable.$this->foreignField"
            );
        }

        $query->andWhere(
            $query->expr()->in("$this->foreignTable.uid", $this->escapeValues($values))
        );
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
    protected function getCounts(QueryBuilder $query): array
    {
        $mainAlias = $this->getMainAlias($query);
        $mainTable = $this->getMainTable($query);
        return (clone $query)
            ->resetQueryParts()
            ->from($mainTable, 'counter')
            ->select(["counter_foreign.uid as identification", "count(*) as count"])
            ->innerJoin(
                "counter",
                $this->foreignTable,
                "counter_foreign",
                "counter.uid=counter_foreign.$this->foreignField"
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
    protected function getItems(QueryBuilder $query): array
    {
        $mainAlias = $this->getMainAlias($query);
        $mainTable = $this->getMainTable($query);
        return (clone $query)
            ->resetQueryParts()
            ->from($mainTable, 'counter')
            ->select(["counter_foreign.uid as identification", "counter_foreign.$this->foreignLabel as label"])
            ->innerJoin(
                "counter",
                $this->foreignTable,
                "counter_foreign",
                "counter.uid=counter_foreign.$this->foreignField"
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
     * @param string $name
     * @param array $values
     * @return array
     */
    public function getMeta(ServerRequestInterface $request, array $filterItems, string $name, array $values): array
    {
        return $this->populateFilterItemWithFilterSelectors($request, $filterItems, $name);
    }
}
