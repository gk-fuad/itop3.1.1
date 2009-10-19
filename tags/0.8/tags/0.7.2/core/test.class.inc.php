<?php

require_once('coreexception.class.inc.php');
require_once('attributedef.class.inc.php');
require_once('filterdef.class.inc.php');
require_once('stimulus.class.inc.php');
require_once('MyHelpers.class.inc.php');

require_once('expression.class.inc.php');
require_once('cmdbsource.class.inc.php');
require_once('sqlquery.class.inc.php');

require_once('dbobject.class.php');
require_once('dbobjectsearch.class.php');
require_once('dbobjectset.class.php');

require_once('userrights.class.inc.php');



// Just to differentiate programmatically triggered exceptions and other kind of errors (usefull?)
class UnitTestException extends Exception
{}


/**
 * Improved display of the backtrace
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @author      Denis Flaven <denisflave@free.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     1.1.1.1 $
 */
class ExceptionFromError extends Exception
{
	public function getTraceAsHtml()
	{
		$aBackTrace = $this->getTrace();
		return MyHelpers::get_callstack_html(0, $this->getTrace());
		// return "<pre>\n".$this->getTraceAsString()."</pre>\n";
	}
}


/**
 * Test handler API and basic helpers
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @author      Denis Flaven <denisflave@free.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     1.1.1.1 $
 */
abstract class TestHandler
{
	protected $m_aSuccesses;
	protected $m_aWarnings;
	protected $m_aErrors;
	protected $m_sOutput;

	public function __construct()
	{
		$this->m_aSuccesses = array();
		$this->m_aWarnings = array();
		$this->m_aErrors = array();
	}

	abstract static public function GetName();
	abstract static public function GetDescription();

	protected function DoPrepare() {return true;}
	abstract protected function DoExecute();
	protected function DoCleanup() {return true;}

	protected function ReportSuccess($sMessage, $sSubtestId = '')
	{
		$this->m_aSuccesses[] = $sMessage;
	}

	protected function ReportWarning($sMessage, $sSubtestId = '')
	{
		$this->m_aWarnings[] = $sMessage;
	}

	protected function ReportError($sMessage, $sSubtestId = '')
	{
		$this->m_aErrors[] = $sMessage;
	}

	public function GetResults()
	{
		return $this->m_aSuccesses;
	}

	public function GetWarnings()
	{
		return $this->m_aWarnings;
	}

	public function GetErrors()
	{
		return $this->m_aErrors;
	}

	public function GetOutput()
	{
		return $this->m_sOutput;
	}

	public function error_handler($errno, $errstr, $errfile, $errline)
	{
		// Note: return false to call the default handler (stop the program if an error)

		switch ($errno)
		{
		case E_USER_ERROR:
			$this->ReportError($errstr);
			//throw new ExceptionFromError("Fatal error in line $errline of file $errfile: $errstr");
			break;
		case E_USER_WARNING:
			$this->ReportWarning($errstr);
			break;
		case E_USER_NOTICE:
			$this->ReportWarning($errstr);
			break;
		default:
			throw new ExceptionFromError("Fatal warning in line $errline of file $errfile: $errstr");
			$this->ReportWarning("Unknown error type: [$errno] $errstr");
			echo "Unknown error type: [$errno] $errstr<br />\n";
			break;
		}
		return true; // do not call the default handler
	}

	public function Execute()
	{
		ob_start();
		set_error_handler(array($this, 'error_handler'));
		try
		{
			$this->DoPrepare();
			$this->DoExecute();
		}
		catch (ExceptionFromError $e)
		{
			$this->ReportError($e->getMessage().' - '.$e->getTraceAsHtml());
		}
		catch (Exception $e)
		{
			//$this->ReportError($e->getMessage());
			//$this->ReportError($e->__tostring());
			$this->ReportError($e->getMessage().' - '.$e->getTraceAsString());
		}
		restore_error_handler();
		$this->m_sOutput = ob_get_clean();
		return (count($this->GetErrors()) == 0);
	}
}




/**
 * Test to execute a piece of code (checks if an error occurs)  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestFunction extends TestHandler
{
	// simply overload DoExecute (temporary)
}


/**
 * Test to execute a piece of code (checks if an error occurs)  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestWebServices extends TestHandler
{
	// simply overload DoExecute (temporary)

	static protected function DoPostRequestAuth($sRelativeUrl, $aData, $sLogin = 'admin', $sPassword = '', $sOptionnalHeaders = null)
	{
		$aDataAndAuth = $aData;
		$aDataAndAuth['operation'] = 'login';
		$aDataAndAuth['auth_user'] = $sLogin;
		$aDataAndAuth['auth_pwd'] = $sPassword;

		$sHost = $GLOBALS['_SERVER']['HTTP_HOST'];
		$sUrl = "http://$sHost/$sRelativeUrl";

		return self::DoPostRequest($sUrl, $aDataAndAuth, $sOptionnalHeaders);
	}

	// Source: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
	// originaly named after do_post_request
	// Partially adapted to our coding conventions
	static protected function DoPostRequest($sUrl, $aData, $sOptionnalHeaders = null)
	{
		// $sOptionnalHeaders is a string containing additional HTTP headers that you would like to send in your request.

		$sData = http_build_query($aData);

		$aParams = array('http' => array(
								'method' => 'POST',
								'content' => $sData,
								'header'=> "Content-type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($sData)."\r\n",
								));
		if ($sOptionnalHeaders !== null)
		{
			$aParams['http']['header'] .= $sOptionnalHeaders;
		}
		$ctx = stream_context_create($aParams);

		$fp = @fopen($sUrl, 'rb', false, $ctx);
		if (!$fp)
		{
			throw new Exception("Problem with $sUrl, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false)
		{
			throw new Exception("Problem reading data from $sUrl, $php_errormsg");
		}
		return $response;
	}
}

/**
 * Test to check that a function outputs some values depending on its input  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestFunctionInOut extends TestFunction
{
	abstract static public function GetCallSpec(); // parameters to call_user_func
	abstract static public function GetInOut(); // array of input => output

	protected function DoExecute()
	{
		$aTests = $this->GetInOut();
		if (is_array($aTests))
		{
			foreach ($aTests as $iTestId => $aTest)
			{
				$ret = call_user_func_array($this->GetCallSpec(), $aTest['args']);
				if ($ret != $aTest['output'])
				{
					// Note: to be improved to cope with non string parameters
					$this->ReportError("Found '$ret' while expecting '".$aTest['output']."'", $iTestId);
				}
				else
				{
					$this->ReportSuccess("Found the expected output '$ret'", $iTestId);
				}
			}
		}
		else
		{
			$ret = call_user_func($this->GetCallSpec());
			$this->ReportSuccess('Finished successfully');
		}
	}
}


/**
 * Test to check an URL (Searches for Error/Warning/Etc keywords)  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestUrl extends TestHandler
{
	abstract static public function GetUrl();
	abstract static public function GetErrorKeywords();
	abstract static public function GetWarningKeywords();

	protected function DoExecute()
	{
		return true;
	}
}


/**
 * Test to check a user management module  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestUserRights extends TestHandler
{
	protected function DoExecute()
	{
		return true;
	}
}


/**
 * Test to execute a scenario on a given DB
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestScenarioOnDB extends TestHandler
{
	abstract static public function GetDBHost();
	abstract static public function GetDBUser();
	abstract static public function GetDBPwd();
	abstract static public function GetDBName();

	protected function DoPrepare()
	{
		$sDBHost = $this->GetDBHost();
		$sDBUser = $this->GetDBUser();
		$sDBPwd = $this->GetDBPwd();
		$sDBName = $this->GetDBName();

		CMDBSource::Init($sDBHost, $sDBUser, $sDBPwd);
		if (CMDBSource::IsDB($sDBName))
		{
			CMDBSource::DropDB($sDBName);
		}
		CMDBSource::CreateDB($sDBName);
	}

	protected function DoCleanup()
	{
		// CMDBSource::DropDB($this->GetDBName());
	}
}


/**
 * Test to use a business model on a given DB  
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestBizModel extends TestHandler
{
//	abstract static public function GetDBSubName();
//	abstract static public function GetBusinessModelFile();
	abstract static public function GetConfigFile();

	protected function DoPrepare()
	{
		MetaModel::Startup($this->GetConfigFile(), true); // allow missing DB
		MetaModel::CheckDefinitions();

		// something here to create records... but that's another story
	}

	protected function ResetDB()
	{
		if (MetaModel::DBExists())
		{
			MetaModel::DBDrop();
		}
		MetaModel::DBCreate();
	}

	static protected function show_list($oObjectSet)
	{
		$oObjectSet->Rewind();
		$aData = array();
		while ($oItem = $oObjectSet->Fetch())
		{
			$aValues = array();
			foreach(MetaModel::GetAttributesList(get_class($oItem)) as $sAttCode)
			{
				$aValues[$sAttCode] = $oItem->GetAsHTML($sAttCode);
			}
			//echo $oItem->GetKey()." => ".implode(", ", $aValues)."</br>\n";
			$aData[] = $aValues;
		}
		echo MyHelpers::make_table_from_assoc_array($aData);
	}

	static protected function search_and_show_list(DBObjectSearch $oMyFilter)
	{
		$oObjSet = new CMDBObjectSet($oMyFilter);
		echo $oMyFilter->__DescribeHTML()."' - Found ".$oObjSet->Count()." items.</br>\n";
		self::show_list($oObjSet);
	}

	static protected function search_and_show_list_from_sibusql($sSibuSQL)
	{
		echo $sSibuSQL."...<br/>\n"; 
		$oNewFilter = DBObjectSearch::FromSibuSQL($sSibuSQL);
		self::search_and_show_list($oNewFilter);
	}
}


/**
 * Test to execute a scenario common to any business model (tries to build all the possible queries, etc.)
 *
 * @package     iTopORM
 * @author      Romain Quetiez <romainquetiez@yahoo.fr>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.itop.com
 * @since       1.0
 * @version     $itopversion$
 */
abstract class TestBizModelGeneric extends TestBizModel
{
	static public function GetName()
	{
		return 'Full test on a given business model';
	}

	static public function GetDescription()
	{
		return 'Systematic tests: gets each and every existing class and tries every attribute, search filters, etc.';
	}

	protected function DoPrepare()
	{
		parent::DoPrepare();

		if (!MetaModel::DBExists())
		{
			MetaModel::DBCreate();
		}
		// something here to create records... but that's another story
	}

	protected function DoExecute()
	{
		foreach(MetaModel::GetClasses() as $sClassName)
		{
			if (MetaModel::IsAbstract($sClassName)) continue;

			$oNobody = MetaModel::GetObject($sClassName, 123);
			$oBaby = new $sClassName;
			$oFilter = new DBObjectSearch($sClassName);

			// Challenge reversibility of SibusQL / filter object
			//
			$sExpr1 = $oFilter->ToSibuSQL();
			$oNewFilter = DBObjectSearch::FromSibuSQL($sExpr1);
			$sExpr2 = $oNewFilter->ToSibuSQL();
			if ($sExpr1 != $sExpr2)
			{
				$this->ReportError("Found two different SibuSQL expression out of the (same?) filter: <em>$sExpr1</em> != <em>$sExpr2</em>");
			}

			// Use the filter (perform the query)
			//
			$oSet = new CMDBObjectSet($oFilter);
			$this->ReportSuccess('Found '.$oSet->Count()." objects of class $sClassName");
		}
		return true;
	}
}


?>
