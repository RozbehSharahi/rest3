<?php

namespace RozbehSharahi\Rest3\Service;

use Doctrine\Common\Util\Inflector;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestService
{

    /**
     * @var array
     */
    static protected $bodyCache = [];

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getData(ServerRequestInterface $request): array
    {
        $hash = spl_object_hash($request);
        static::$bodyCache[$hash] = static::$bodyCache[$hash] ?: $request->getBody()->__toString();
        return json_decode(static::$bodyCache[$hash], true) ?: [];
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getParameters(ServerRequestInterface $request): array
    {
        return array_replace_recursive(
            $request->getQueryParams(),
            $this->getData($request)
        );
    }

    /**
     * Gets the current route key
     *
     * Example '/rest3/seminar/1` => `seminar`
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function getRouteKey(ServerRequestInterface $request): string
    {
        $routeKey = explode('/', trim($request->getUri()->getPath(), '/'))[1];
        return Inflector::singularize($routeKey);
    }

    /**
     * @return int
     */
    public function getCurrentLanguageUid(): int
    {
        return (int)GeneralUtility::_GP('L');
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getIncludes(ServerRequestInterface $request): array
    {
        return $request->getQueryParams()['include'] ? explode(',', $request->getQueryParams()['include']) : [];
    }

}
