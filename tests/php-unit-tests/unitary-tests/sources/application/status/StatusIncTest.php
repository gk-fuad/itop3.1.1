<?php
namespace Combodo\iTop\Test\UnitTest\Status;



use Combodo\iTop\Application\Status\Status;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use Config;
use MySQLException;
use function Combodo\iTop\Application\Status\StatusCheckConfigFile;
use function Combodo\iTop\Application\Status\StatusGetAppRoot;
use function Combodo\iTop\Application\Status\StatusStartup;

if (!defined('DEBUG_UNIT_TEST')) {
    define('DEBUG_UNIT_TEST', true);
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class StatusIncTest extends ItopTestCase {

    /**
     * @var string
     */
    protected $sAppRoot = '';

	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('sources/application/status/Status.php');
	}

    /**
     * @expectedException \Exception
     */
    public function testStatusGetAppRootWrongPath() {
        $sAppRootFilenamewrong = 'approot.inc.php_wrong';

		$oStatus = new Status();
		$this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusGetAppRoot", $oStatus, [$sAppRootFilenamewrong]);
    }

    /**
     * 
     */
    public function testStatusGetAppRootGood() {
	    $oStatus = new Status();
	    $this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusGetAppRoot", $oStatus, []);

        $this->assertNotEmpty(APPROOT);
    }

    /**
     * @expectedException \Exception
     */
    public function testStatusCheckConfigFileWrongPath() {
        $sConfigFilenamewrong = 'config-itop.php_wrong';

	    $oStatus = new Status();
	    $this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusCheckConfigFile", $oStatus, [$sConfigFilenamewrong]);
    }

    public function testStatusCheckConfigFileGood() {
	    $oStatus = new Status();
	    $this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusCheckConfigFile", $oStatus, []);

        $this->assertTrue(true);
    }

    public function testStatusStartupWrongDbPwd()
    {
	    $this->expectException(MySQLException::class);
	    $oStatus = new Status();
	    $this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusCheckConfigFile", $oStatus, []);

	    $this->RequireOnceItopFile('core/cmdbobject.class.inc.php');
	    $this->RequireOnceItopFile('application/utils.inc.php');
	    $this->RequireOnceItopFile('core/contexttag.class.inc.php');

	    $oConfigWrong = new Config(ITOP_DEFAULT_CONFIG_FILE);
	    $oConfigWrong->Set('db_pwd', $oConfigWrong->Get('db_pwd').'_unittest');
	    $this->InvokeNonPublicMethod(Status::class, "StatusStartup", $oStatus, [$oConfigWrong]);
    }

    /**
     * 
     */
    public function testStatusStartupGood() {
	    $oStatus = new Status();
	    $this->InvokeNonPublicMethod("Combodo\iTop\Application\Status\Status", "StatusStartup", $oStatus, []);

        $this->assertTrue(true);
    }

}
