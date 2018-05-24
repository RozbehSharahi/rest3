<?php

namespace RozbehSharahi\Rest3\Pagination;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class Pagination implements PaginationInterface
{

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var int
     */
    protected $pageSize = 20;

    /**
     * @param $query
     * @return PaginationInterface
     * @throws \Exception
     */
    public function setQuery($query): PaginationInterface
    {
        if (!$query instanceof QueryInterface) {
            throw new \Exception(static::class . ' accepts only ' . QueryInterface::class);
        }
        $this->query = clone $query;
        return $this;
    }

    /**
     * @param int $pageSize
     * @return PaginationInterface
     */
    public function setPageSize(int $pageSize): PaginationInterface
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Get the page you want
     *
     * @param int $pageIndex
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \Exception
     */
    public function getPage(int $pageIndex = 1)
    {
        if ($pageIndex <= 0) {
            throw new \Exception('Page for Pagination must not be smaller or equals 0');
        }

        return (clone $this->query)
            ->setOffset(($pageIndex - 1) * ($this->pageSize))
            ->setLimit($this->pageSize)
            ->execute();
    }

    /**
     * @param int $pageIndex
     * @return array
     * @throws \Exception
     */
    public function getPaginator(int $pageIndex): array
    {
        if ($pageIndex <= 0) {
            throw new \Exception('Page for Pagination must not be smaller or equals 0');
        }

        $pageCount = (int)ceil($this->query->count() / $this->pageSize) ?: 1;

        return [
            'currentPage' => $pageIndex,
            'pageCount' => $pageCount,
            'buttons' => $this->getButtons($pageIndex)
        ];
    }

    /**
     * @param int $pageIndex
     * @return array
     */
    protected function getButtons(int $pageIndex)
    {
        $pageCount = (int)ceil($this->query->count() / $this->pageSize) ?: 1;

        // In case we have no results or just one page, don't render anything
        if ($pageCount === 0 || $pageCount === 1) {
            return [];
        }

        $buttons = array_map(function ($page) use ($pageIndex) {
            return [
                'value' => $page,
                'active' => $page === $pageIndex
            ];
        }, range(1, $pageCount));

        return count($buttons) > 1 ? $buttons : [];
    }

    /**
     * @param int $pageIndex
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getMeta(int $pageIndex, ServerRequestInterface $request): array
    {
        $paginator = $this->getPaginator($pageIndex);
        $currentParams = $request->getQueryParams();
        unset($currentParams['eID']);

        $links = [];
        $links['first'] = $request->getUri()->withQuery(
            http_build_query(array_replace_recursive(
                $currentParams,
                ['page' => [PaginationInterface::QUERY_PARAM_PAGE_NUMBER => 1]]
            ))
        )->__toString();
        $links['last'] = $request->getUri()->withQuery(
            http_build_query(array_replace_recursive(
                $currentParams,
                ['page' => [PaginationInterface::QUERY_PARAM_PAGE_NUMBER => $paginator['pageCount']]]
            ))
        )->__toString();
        if ($paginator['currentPage'] - 1 > 0) {
            $links['prev'] = $request->getUri()->withQuery(
                http_build_query(array_replace_recursive(
                    $currentParams,
                    ['page' => [PaginationInterface::QUERY_PARAM_PAGE_NUMBER => $paginator['currentPage'] - 1]]
                ))
            )->__toString();
        }
        if ($paginator['currentPage'] + 1 <= $paginator['pageCount']) {
            $links['next'] = $request->getUri()->withQuery(
                http_build_query(array_replace_recursive(
                    $currentParams,
                    ['page' => [PaginationInterface::QUERY_PARAM_PAGE_NUMBER => $paginator['currentPage'] + 1]]
                ))
            )->__toString();
        }

        return [
            'meta' => [
                'pagination' => [
                    'pageNumber' => $paginator['currentPage'],
                    'pageCount' => $paginator['pageCount'],
                    'itemsCount' => $this->query->count()
                ]
            ],
            'links' => $links
        ];
    }
}
