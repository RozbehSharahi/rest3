<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

/**
 * Class RenderingTest
 */
class LoadRexampleTest extends FunctionalTestBase
{
    /**
     * @test
     */
    public function rexampleIsLoaded()
    {
        self::assertNotEmpty($GLOBALS['TYPO3_LOADED_EXT']['rexample']);
    }

    /**
     * @test
     */
    public function rexampleTypoScriptIsSet()
    {
        self::assertNotEmpty($this->getConfiguration()['plugin.']['tx_rexample.']);
    }

}
