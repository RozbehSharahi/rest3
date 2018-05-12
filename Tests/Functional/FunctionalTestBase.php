<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FunctionalTestBase extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/rest3', 'typo3conf/ext/rexample'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    /**
     * @return object|ObjectManager
     */
    public function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return ConfigurationManagerInterface
     */
    public function getConfigurationManager()
    {
        return $this->getObjectManager()->get(ConfigurationManagerInterface::class);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->getConfigurationManager()->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }

    /**
     * Set data for a table
     *
     * @param string $tableName
     * @param array $data
     */
    public function setUpDatabaseData($tableName, $data)
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);

        foreach ($data as $row) {

            try {
                // With mssql, hard setting uid auto-increment primary keys is only allowed if
                // the table is prepared for such an operation beforehand
                $platform = $connection->getDatabasePlatform();
                $sqlServerIdentityDisabled = false;
                if ($platform instanceof SQLServerPlatform) {
                    try {
                        $connection->exec('SET IDENTITY_INSERT ' . $tableName . ' ON');
                        $sqlServerIdentityDisabled = true;
                    } catch (DBALException $e) {
                        // Some tables like sys_refindex don't have an auto-increment uid field and thus no
                        // IDENTITY column. Instead of testing existance, we just try to set IDENTITY ON
                        // and catch the possible error that occurs.
                    }
                }

                // Some DBMS like mssql are picky about inserting blob types with correct cast, setting
                // types correctly (like Connection::PARAM_LOB) allows doctrine to create valid SQL
                $types = [];
                $tableDetails = $connection->getSchemaManager()->listTableDetails($tableName);
                foreach ($row as $columnName => $columnValue) {
                    $types[] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }

                // Insert the row
                $connection->insert($tableName, $row, $types);

                if ($sqlServerIdentityDisabled) {
                    // Reset identity if it has been changed
                    $connection->exec('SET IDENTITY_INSERT ' . $tableName . ' OFF');
                }

            } catch (DBALException $e) {
                $this->fail('SQL Error for table "' . $tableName . '": ' . LF . $e->getMessage());
            }
        }
    }

    /**
     * Creates a page that is need to do typo script configuration
     * @param string $typoScript
     */
    protected function setUpTestWebsite(string $typoScript = '')
    {
        // Add a test page
        $this->setUpDatabaseData('pages', [
            [
                'uid' => 1,
                'title' => 'TestWebsite',
            ]
        ]);

        // Add a test image
        $this->setUpDatabaseData('sys_file', [
            [
                "uid" => 1,
                "pid" => 0,
                "tstamp" => 1506194395,
                "last_indexed" => 1506194395,
                "missing" => 0,
                "storage" => 1,
                "type" => "2",
                "metadata" => 0,
                "identifier" => "/user_upload/example_image.jpg",
                "identifier_hash" => "",
                "folder_hash" => "",
                "extension" => "jpg",
                "mime_type" => "image/jpeg",
                "name" => "example_image.jpg",
                "sha1" => "",
                "size" => 3818238,
                "creation_date" => 1506194395,
                "modification_date" => 1506194395
            ]
        ]);

        // Set up page repository
        $GLOBALS['TSFE']->sys_page = $this->getObjectManager()->get(PageRepository::class);

        // Some typo scripts we need
        $this->setUpFrontendRootPage('1', []);
        $this->addTypoScriptToTemplateRecord('1', $typoScript);
    }

    /**
     * Login a user for functional test
     *
     * @param int $uid
     */
    protected function setUpLoggedInUser($uid)
    {
        $GLOBALS['TSFE'] = $this->getObjectManager()->get(TypoScriptFrontendController::class, null, 1, 0);

        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = &$GLOBALS['TSFE'];
        $frontendController->initFEuser();
        $frontendController->fe_user->user = ['uid' => $uid];
    }

}
