<?php
namespace RozbehSharahi\Rest3\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class RenderingTest
 */
class LoadRexampleTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/rest3','typo3conf/ext/rexample'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    /**
     * @test
     */
    public function rexampleIsLoaded()
    {
        self::assertNotEmpty($GLOBALS['TYPO3_LOADED_EXT']['rexample']);
    }

}
