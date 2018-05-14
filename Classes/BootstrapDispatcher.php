<?php

namespace RozbehSharahi\Rest3;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Service\RequestService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * Bootstrap Dispatcher
 *
 * This class is not instantiated by ObjectManager, that is why we have it here to be kind of a bootstrap
 *  that will call the actual default dispatcher of rest3.
 *
 * Please keep in mind, that you might override the default dispatcher by implementing and configuring
 *  DispatcherInterface.
 */
class BootstrapDispatcher
{

    /**
     * @var ObjectManager
     */
    protected static $objectManager;

    /**
     * BootstrapDispatcher constructor.
     */
    public function __construct()
    {
        $this->initTypo3(0);
    }

    /**
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        return self::getDispatcher()->dispatch($request, $response);
    }

    /**
     * @return DispatcherInterface
     */
    protected static function getDispatcher(): DispatcherInterface
    {
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = self::getObjectManager()->get(DispatcherInterface::class);
        return $dispatcher;
    }

    /**
     * @return RequestService
     */
    protected static function getRequestService(): RequestService
    {
        /** @var RequestService $requestService */
        $requestService = self::getObjectManager()->get(RequestService::class);
        return $requestService;
    }

    /**
     * Initialize the typo3
     *
     * @param int $pageUid
     *
     * @return void
     */
    private function initTypo3($pageUid = 1)
    {
        // By calling this it will also init TYPO3
        self::getFrontendController($pageUid);
    }

    /**
     * @param $pageUid
     * @return TypoScriptFrontendController
     */
    private static function getFrontendController($pageUid): TypoScriptFrontendController
    {
        if ($GLOBALS['TSFE']) {
            return $GLOBALS['TSFE'];
        }

        $GLOBALS['TSFE'] = self::getObjectManager()->get(
            TypoScriptFrontendController::class,
            null,
            $pageUid,
            0,
            1,
            'rest3-cache-hash'
        );

        EidUtility::initFeUser();
        EidUtility::initTCA();

        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(true);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->getConfigArray();

        // Language
        $sysLanguageUid = self::getRequestService()->getCurrentLanguageUid();
        $GLOBALS['TSFE']->sys_language_uid = $sysLanguageUid;
        $GLOBALS['TSFE']->sys_language_content = $sysLanguageUid;
        $GLOBALS['TSFE']->config['config']['sys_language_uid'] = $sysLanguageUid;
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();

        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = $GLOBALS['TSFE'];

        return $frontendController;
    }

    /**
     * @return ObjectManagerInterface
     */
    protected static function getObjectManager(): ObjectManagerInterface
    {
        if (!is_null(self::$objectManager)) {
            return self::$objectManager;
        }
        /** @var ObjectManagerInterface $objectManager */
        self::$objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return self::$objectManager;
    }

    /**
     * @return string
     */
    public static function getEntryPoint(): string
    {
        return self::getDispatcher()->getEntryPoint();
    }

    /**
     * @return bool
     */
    public static function isRestRoute(): bool
    {
        return strpos($_SERVER['REQUEST_URI'], self::getEntryPoint()) === 0;
    }
}