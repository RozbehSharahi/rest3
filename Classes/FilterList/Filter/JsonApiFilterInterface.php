<?php

namespace RozbehSharahi\Rest3\FilterList\Filter;

use Psr\Http\Message\ServerRequestInterface;

interface JsonApiFilterInterface
{

    /**
     * This will be merged into the filter item
     *
     * @param ServerRequestInterface $request
     * @param array $filterItems
     * @param array $values
     * @param string $name
     * @return array
     */
    public function getMeta(ServerRequestInterface $request, array $filterItems, array $values, string $name): array;

}