<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Doctrine\DBAL\Query\QueryBuilder;

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
