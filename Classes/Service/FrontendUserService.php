<?php

namespace RozbehSharahi\Rest3\Service;

use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;


class FrontendUserService
{

    /**
     * @var FrontendUserAuthentication
     */
    protected $frontendUserAuthentication;

    /**
     * @var FrontendUser
     */
    protected $currentUser;

    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     * @var FrontendUserGroupRepository
     */
    protected $frontendUserGroupRepository;

    /**
     * @param FrontendUserGroupRepository $frontendUserGroupRepository
     */
    public function injectFrontendUserGroupRepository(FrontendUserGroupRepository $frontendUserGroupRepository)
    {
        $this->frontendUserGroupRepository = $frontendUserGroupRepository;
    }

    /**
     * Initialize the object
     *
     * This is called by objectManager. In tests you may call this as
     * constructor or configure your classes yourself.
     */
    public function initializeObject()
    {
        $this->frontendUserAuthentication = &$GLOBALS['TSFE']->fe_user;
    }

    /**
     * Login a given user
     *
     * By passing a valid database user this will create the session. It is
     * quite a hack to login a user in TYPO3. Nevertheless there is no better
     * code for archiving this by now.
     *
     * @param FrontendUser $user
     * @throws \ReflectionException
     */
    public function loginUser(FrontendUser $user)
    {
        $this->frontendUserAuthentication->checkPid = 0;
        $this->frontendUserAuthentication->forceSetCookie = true;
        $info = $this->frontendUserAuthentication->getAuthInfoArray();
        $user = $this->frontendUserAuthentication->fetchUserRecord($info['db_user'], $user->getUsername());

        $GLOBALS['TSFE']->loginUser = 1;
        $this->frontendUserAuthentication->is_permanent = true;
        $this->frontendUserAuthentication->lifetime = 60 * 60 * 24 * 7;
        $this->frontendUserAuthentication->user['ses_permanent'] = true;
        $this->frontendUserAuthentication->createUserSession($user);

        // Set cookie hack for TYPO3 bug, that setSessionCookie is protected
        $reflection = new \ReflectionClass($this->frontendUserAuthentication);
        $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
        $setSessionCookieMethod->setAccessible(true);
        $setSessionCookieMethod->invoke($this->frontendUserAuthentication);

        $GLOBALS['TSFE']->fe_user->user = $user;
    }

    /**
     * @param FrontendUser $user
     */
    public function authenticateUser(FrontendUser $user)
    {
        $this->frontendUserAuthentication->checkPid = 0;
        $this->frontendUserAuthentication->forceSetCookie = true;
        $info = $this->frontendUserAuthentication->getAuthInfoArray();
        $user = $this->frontendUserAuthentication->fetchUserRecord($info['db_user'], $user->getUsername());

        $GLOBALS['TSFE']->loginUser = 1;
        $this->frontendUserAuthentication->is_permanent = false;
        $this->frontendUserAuthentication->lifetime = 60;
        $this->frontendUserAuthentication->user['ses_permanent'] = false;
        $this->frontendUserAuthentication->createUserSession($user);

        $GLOBALS['TSFE']->fe_user->user = $user;
    }

    /**
     * Check if user is logged in
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        return !empty($this->getCurrentUserId());
    }

    /**
     * Will logout the current user
     */
    public function logoutCurrentUser()
    {
        $GLOBALS['TSFE']->loginUser = 0;
        $this->frontendUserAuthentication->logoff();
    }

    /**
     * Checks credentials by a user
     *
     * @param string $username
     * @param string $password
     * @return false|FrontendUser
     */
    public function checkCredentials($username, $password)
    {
        /** @var FrontendUser $foundUser */
        $saltingInstance = SaltFactory::getSaltingInstance(null);
        $foundUser = $this->findUser($username);

        return !empty($foundUser) && $saltingInstance->checkPassword($password, $foundUser->getPassword())
            ? $foundUser : false;
    }

    /**
     * Get the current logged in user
     *
     * @return FrontendUser
     */
    public function getCurrentUser()
    {
        /** @var FrontendUser $user */
        $user = $this->getCurrentUserId() ? $this->frontendUserRepository->findByUid($this->getCurrentUserId()) : null;
        return $user;
    }

    /**
     * Get the TYPO3 FrontendUserAuthentication object
     *
     * @return FrontendUserAuthentication
     */
    public function getFrontendUserAuthentication()
    {
        return $this->frontendUserAuthentication;
    }

    /**
     * @return FrontendUserRepository
     */
    public function getFrontendUserRepository()
    {
        return $this->frontendUserRepository;
    }


    /**
     * @return FrontendUserGroupRepository
     */
    public function getFrontendUserGroupRepository()
    {
        return $this->frontendUserGroupRepository;
    }

    /**
     * Get user id
     *
     * Will get the users id from TSFE
     *
     * @return null|integer
     */
    protected function getCurrentUserId()
    {
        return $this->frontendUserAuthentication->user['uid'] ?? null;
    }

    /**
     * @param string $username
     * @return DomainObjectInterface|null
     */
    public function findUser(string $username)
    {
        $query = $this->frontendUserRepository->createQuery();
        $query->setQuerySettings(
            (new Typo3QuerySettings())
                ->setRespectStoragePage(false)
        );
        $query->matching($query->equals('username', $username));
        /** @var FrontendUser $foundUser */
        $foundUser = $query->execute()->getFirst();
        return $foundUser;
    }

}
