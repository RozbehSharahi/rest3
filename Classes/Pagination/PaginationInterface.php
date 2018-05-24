<?php

namespace RozbehSharahi\Rest3\Pagination;

use Psr\Http\Message\ServerRequestInterface;

interface PaginationInterface
{

    const QUERY_PARAM_PAGE_NUMBER = 'number';
    const QUERY_PARAM_PAGE_SIZE = 'size';

    /**
     * @param $query
     * @return PaginationInterface
     */
    public function setQuery($query): PaginationInterface;

    /**
     * Get the page you want
     *
     * @param int $pageIndex
     * @return mixed
     */
    public function getPage(int $pageIndex = 1);

    /**
     * Get paginator
     *
     * Should return the current page, the page count and buttons corresponding to the current page ($page).
     *
     * @param int $pageIndex
     * @return array
     */
    public function getPaginator(int $pageIndex): array;

    /**
     * @param int $pageSize
     * @return PaginationInterface
     */
    public function setPageSize(int $pageSize): PaginationInterface;

    /**
     * @param int $pageIndex
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getMeta(int $pageIndex, ServerRequestInterface $request): array;

}