<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;

class AttributeFilter implements FilterInterface, JsonApiFilterInterface
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
     * @param string $name
     * @return QueryBuilder Will be used to continue query filtering
     */
    public function addFilter(QueryBuilder $query, array $values, string $name): QueryBuilder
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
