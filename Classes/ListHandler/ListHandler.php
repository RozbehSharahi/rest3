<?php

namespace RozbehSharahi\Rest3\ListHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\FilterList\Filter\FilterInterface;
use RozbehSharahi\Rest3\FilterList\FilterListInterface;
use RozbehSharahi\Rest3\Normalizer\Normalizer;
use RozbehSharahi\Rest3\Pagination\PaginationInterface;
use RozbehSharahi\Rest3\Route\RouteManagerInterface;
use RozbehSharahi\Rest3\Service\RequestService;
use RozbehSharahi\Rest3\Service\ResponseService;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ListHandler implements ListHandlerInterface, DispatcherInterface
{

    /**
     * @var string
     */
    protected $routeKey;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @var RouteManagerInterface
     */
    protected $routeManager;

    /**
     * @param RouteManagerInterface $routeManager
     */
    public function injectRouteManager(RouteManagerInterface $routeManager)
    {
        $this->routeManager = $routeManager;
    }

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * @param ResponseService $responseService
     */
    public function injectResponseService(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * @var RequestService
     */
    protected $requestService;

    /**
     * @param RequestService $requestService
     */
    public function injectRequestService(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * @var Normalizer
     */
    protected $normalizer;

    /**
     * @param Normalizer $normalizer
     */
    public function injectNormalizer(Normalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $routeKey
     * @return ResponseInterface
     */
    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $routeKey = null
    ): ResponseInterface {
        $this->routeKey = $routeKey;
        $parameters = $request->getQueryParams()[ListHandlerInterface::QUERY_PARAM];

        $this->assert(is_array($parameters), 'Invalid parameter for list handler (_listHandler). Needs array.');
        $this->assert(is_string($parameters['filterSet']), '_listHandler[filterSet] has to be a string');

        /** @var FilterListInterface $filterList */
        $filterList = $this->objectManager->get(FilterListInterface::class);
        $filterList
            ->setRequest($request)
            ->setBaseQuery($this->getBaseQuery())
            ->setFilterSet($this->getFilterSet($parameters['filterSet']))
            ->setFilters($this->getFilters($request));
        $ids = array_column($filterList->getQuery()->execute()->fetchAll(), 'uid');

        $query = $this->getRepository()->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $domainObjectsQuery = $query->matching($query->in('uid', !empty($ids) ? $ids : [-1]));

        /** @var PaginationInterface $pagination */
        $pagination = $this->objectManager->get(PaginationInterface::class);
        $pageSize = (int)$request->getQueryParams()['page'][PaginationInterface::QUERY_PARAM_PAGE_SIZE] ?: 20;
        $pageNumber = (int)$request->getQueryParams()['page'][PaginationInterface::QUERY_PARAM_PAGE_NUMBER] ?: 1;
        $page = $pagination
            ->setQuery($domainObjectsQuery)
            ->setPageSize($pageSize)
            ->getPage($pageNumber);

        return $this->responseService->jsonResponse(
            array_replace_recursive(
                $pagination->getMeta($pageNumber, $request),
                ['meta' => ['filter' => ['items' => $filterList->getFilterItems()]]],
                $this->normalizer->normalize($page, $this->requestService->getIncludes($request))
            )
        );
    }

    /**
     * @param $filterSetName
     * @return FilterInterface[]
     */
    public function getFilterSet($filterSetName)
    {
        /** @var FilterInterface[] $filterSet */
        $filterSet = [];
        $filterConfiguration = $this->routeManager
            ->getRouteConfiguration($this->routeKey, "listHandler.filterSets.$filterSetName");

        $this->assert(!empty($filterConfiguration), "No configuration for filter-set `$filterSetName`");
        $this->assert(is_array($filterConfiguration), "Wrong configuration for filter-set `$filterSetName`");

        foreach ($filterConfiguration as $name => $configuration) {
            $filter = $configuration['className'];
            unset($configuration['className']);
            $filterSet[$name] = $this->objectManager->get($filter);
            $filterSet[$name]->setConfiguration($configuration);
        }
        return $filterSet;
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        $repositoryName = $this->routeManager->getRouteConfiguration($this->routeKey, 'repositoryName');
        $this->assert(!empty($repositoryName), 'Repository name (repositoryName) is not set for ' . $this->routeKey);

        /** @var RepositoryInterface $repository */
        $repository = $this->objectManager->get(
            $repositoryName
        );
        return $repository;
    }

    /**
     * @return QueryInterface|QueryBuilder|\TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getBaseQuery()
    {
        /** @var QueryInterface $query */
        $query = $this->getRepository()->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->greaterThan('uid', 0));
        return $query;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getFilters(ServerRequestInterface $request): array
    {
        $filters = $request->getQueryParams()['filter'] ?: [];
        $this->assert(is_array($filters), 'Filter parameter must be of type array: (?filter[attribute]=...)');
        foreach ($filters as $index => $filter) {
            if (is_string($filter)) {
                $filters[$index] = [$filter];
            }
            foreach ($filters[$index] as $value) {
                $this->assert(is_string($value), 'Values of a filter have to be of type string');
            }
        }
        return $filters;
    }

    /**
     * @param bool $assertion
     * @param mixed $message
     * @throws Exception
     */
    protected function assert(bool $assertion, $message)
    {
        if (!$assertion) {
            throw Exception::create()->addError($message);
        }
    }

}
