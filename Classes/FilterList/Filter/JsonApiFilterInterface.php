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
     * @param string $name
     * @param array $values
     * @return array
     */
    public function getMeta(ServerRequestInterface $request, array $filterItems, string $name, array $values): array;

}