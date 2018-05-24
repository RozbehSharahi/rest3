<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Pagination\PaginationInterface;

trait FilterTrait
{

    /**
     * @param array $configuration
     * @return FilterInterface
     */
    public function setConfiguration(array $configuration): FilterInterface
    {
        // validate configuration
        foreach ($this->configurationProperties as $name) {
            $this->assert($configuration[$name], "Property `$name` configuration was not set for " . static::class);
            $this->assert(property_exists($this, $name), "Property `$name` does not exist on " . static::class);
        }

        // set configuration to properties
        foreach ($configuration as $attribute => $value) {
            $this->assertConfigurationProperty($attribute);
            $this->{$attribute} = $value;
        }

        /** @var FilterInterface $filterInterface */
        $filterInterface = $this;
        return $filterInterface;
    }

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
     * @return string
     */
    protected function getMainTable(QueryBuilder $query): string
    {
        return trim($query->getQueryPart('from')[0]['table'], '`');
    }

    /**
     * @param QueryBuilder $query
     * @param string $tableName
     * @return bool
     */
    protected function hasJoin(QueryBuilder $query, string $tableName): bool
    {
        $joins = reset($query->getQueryPart('join')) ?: [];
        foreach ($joins as $join) {
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

    /**
     * @param string $unescaped
     * @return string
     */
    protected function escape(string $unescaped): string
    {
        $replacements = array(
            "\x00" => '\x00',
            "\n" => '\n',
            "\r" => '\r',
            "\\" => '\\\\',
            "'" => "\'",
            '"' => '\"',
            "\x1a" => '\x1a'
        );
        return strtr($unescaped, $replacements);
    }

    /**
     * @todo remove this again and do it correct,
     * just been to tired to fix this.
     *
     * @param array $values
     * @param string $quote
     * @return array
     */
    protected function escapeValues(array $values, string $quote = '"'): array
    {
        return array_map(function ($value) use ($quote) {
            return $quote . $this->escape($value) . $quote;
        }, $values);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $filterItems
     * @param string $name
     * @return array
     */
    protected function populateFilterItemWithFilterSelectors(
        ServerRequestInterface $request,
        array $filterItems,
        string $name
    ): array {
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        // Make sure filter is part of the query
        $query['filter'] = $query['filter'] ?: [];

        // Transform string filters to array
        foreach ($query['filter'] as $filterName => $filterValue) {
            if (is_string($filterValue)) {
                $query['filter'][$filterName] = [$query['filter'][$filterName]];
            }
        }

        // When setting new filter we have to reset page number settings
        unset($query['page'][PaginationInterface::QUERY_PARAM_PAGE_NUMBER]);

        foreach ($filterItems as &$filterItem) {
            $filterItem['links'] = [];
            if (is_string($query['filter'][$name])) {
                $query['filter'][$name] = [$query['filter'][$name]];
            };
            $isActive = in_array($filterItem['identification'], $query['filter'][$name] ?: []);
            if (!$isActive) {
                $activateQuery = $query;
                $activateQuery['filter'][$name][] = $filterItem['identification'];
                $filterItem['links']['activate'] = $request->getUri()
                    ->withQuery(http_build_query($activateQuery))->__toString();
            } else {
                $indexOfValue = array_search($filterItem['identification'], $query['filter'][$name] ?: []);
                $activateQuery = $query;
                if ($indexOfValue !== false) {
                    unset($activateQuery['filter'][$name][$indexOfValue]);
                }
                $filterItem['links']['deactivate'] = $request->getUri()
                    ->withQuery(http_build_query($activateQuery))->__toString();
            }
        }
        return $filterItems;
    }

    /**
     * @param bool $assertion
     * @param mixed $message
     * @throws \Exception
     */
    protected function assert($assertion, $message)
    {
        if (!$assertion) {
            throw new \Exception($message);
        }
    }

    /**
     * @param string $attribute
     */
    protected function assertConfigurationProperty(string $attribute): void
    {
        $this->assert(
            in_array($attribute, $this->configurationProperties),
            "Property `$attribute` is not allowed on " . static::class
        );
    }

}
