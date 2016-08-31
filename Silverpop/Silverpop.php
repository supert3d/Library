<?php
/**
  * @author Tony Collings <tony@tonycollings.com>
  * @license GPL
  * @version 1.0  
  *
  */


/**
 * Based on v8.8 of the SilverPOP XML API. 
 * Examples of other approaches: https://github.com/simpleweb/SilverpopPHP/tree/master/src/Silverpop
 * 
 * EXAMPLE 1: Basic instantation and method call. 
 * $oSP = new Silverpop('[uid]','[pwd]');
 * $oSP->fnSetList('[listname]');
 * echo $oSP->sListID; 
 * echo $oSP->sListName; 
 * echo $oSP->fnGetRecipientID('tony@tonycollings.com');
 * 
 * EXAMPLE 2: Get all lists belonging to parent... 
 * $oResponse = $oSP->fnRequest('GetLists',array(
 * 	'VISIBILITY' => 1, // 1 = Shared, 0 = Private
 * 	'LIST_TYPE' => 18, // ALL
 * 	'INCLUDE_ALL_LISTS' => 'true'
 * ));	
 * $aLists = $oResponse->xpath('//LIST[PARENT_NAME="'.$oSP->sListName.'"]');  
 * 
 */
 

include('ArrayToXML.php'); // Include a utility class to convert Array to an XML. Has to be XML payload. 

class Silverpop {
	
	
	private $sUID = NULL; // (string) UserID
	private $sPWD = NULL; // (string) Password
	private $sSessionID = NULL; // (string) Populated on successful login. 
	private $sSessionEncoding = NULL; // (string) Populated on successful login. 
	private $sOrganizationID = NULL; // (string) Populated on successful login. 
	private $aData = array(); // (array) Property for storing overloaded data. 
	private $sEndPoint = 'http://api5.silverpop.com/XMLAPI'; // (string) NOTE: Engage instance. http://api[engageInstance].silverpop.com/etc... 
	private $sFTP = 'transfer5.silverpop.com'; 
	private $aOperations = array(
		'SendMailing', 
		'ForwardToFriend',
		'GetContactMailingDetails',
		'PurgeData',
		'AddRecipient',
		'DoubleOptInRecipient',
		'OptOutRecipient',
		'SelectRecipientData',
		'Login',
		'Logout',
		'ImportList',
		'ExportList',
		'AddListColumn',
		'GetListMetaData',
		'ListRecipientMailings',
		'RemoveRecipient',
		'GetLists',
		'CreateTable',
		'JoinTable',
		'InsertUpdateRelationalTable',
		'DeleteRelationalTableData',
		'ImportTable',
		'ExportTable',
		'PurgeTable',
		'DeleteTable',
		'CreateContactList',
		'AddContactToContactList',
		'AddContactToProgram',
		'CreateQuery',
		'CalculateQuery',
		'SetColumnValue>',
		'TrackingMetricExport',
		'RawRecipientDataExport',
		'WebTrackingDataExport',
		'GetReportIdByDate',
		'GetSentMailingsForOrg',
		'GetSentMailingsForUser',
		'GetSentMailingsForList',
		'GetAggregateTrackingForMailing',
		'GetAggregateTrackingForOrg',
		'GetAggregateTrackingForUser',
		'GetJobStatus',
		'DeleteJob',
		'ScheduleMailing',
		'PreviewMailing',
		'GetMessageGroupDetails',
		'ImportDCRuleset',
		'ExportDCRuleset',
		'ListDCRulesetsForMailing',
		'GetDCRuleset',
		'ReplaceDCRuleset',
		'ValidateDCRuleset',
		'DeleteDCRuleset',
		'GetMailingTemplates',
		'ExportMailingTemplate'
	); 
	
	public $sListID = NULL; // (string) ID for list (useful for storing and referencing later)
	public $sListName = NULL; // (string) List name. 


  /**
   * @var string $sUID - Username of API account 
   * @var string $sUID - Password of API account 
   * @return void
   *
   */
	public function __construct($sUID,$sPWD){
		$this->fnLogin($sUID,$sPWD);
	}

  /**
   * @return void
   * Logout
   *
   */
	public function __destruct(){
		if($this->sSessionID) 
			$this->fnLogOut(); 	
	}

	public function __set($sName,$mValue){
		$this->aData[$sName] = $mValue; 
	}
	public function __get($sName){
		if(array_key_exists($sName,$this->aData)){
			return $this->aData[$sName]; 	
		}
	}	

  /**
   * @var string $sName - Username of API account 
   * @var string $sUID - Password of API account 
   * @return bool $bReturn  
   *
   */
	public function fnLogin($sUID,$sPWD){

		
		$bReturn = false; 
		$bUseSession = false; 
		
		// Check to see if we can use $_SESSION information 
		if(isset($_SESSION['SP_DATA']['sessionGenerated'])){
			$bUseSession = true; 
			$iExpires = strtotime("+15 min",$_SESSION['SP_DATA']['sessionGenerated']); // 15-20 min timeout on SilverPOP API.  
			if(time() > $iExpires){
				$bUseSession = false; // Renew!  
			}
		}else{
			$bUseSession = false; 	
		}
		
		
		
		if($bUseSession){
			
			$this->sSessionID = $_SESSION['SP_DATA']['sessionID']; 	
			$this->sSessionEncoding = $_SESSION['SP_DATA']['sessionEncoding']; 	
			$this->sOrganizationID = $_SESSION['SP_DATA']['orgID'];
			$bReturn = true; 
		
		}else{
		
			$oResponse = $this->fnRequest('Login',array(
				'USERNAME' => $sUID,
				'PASSWORD' => $sPWD
			)); 
			$bReturn = (strtolower((string)$oResponse->SUCCESS) == 'true')?true:false;
			if($bReturn){
				$this->sUID = $sUID; 
				$this->sPWD = $sPWD; 
				$this->sSessionID = (string)$oResponse->SESSIONID; 	
				$this->sSessionEncoding = (string)$oResponse->SESSION_ENCODING; 	
				$this->sOrganizationID = (string)$oResponse->ORGANIZATION_ID;
				// Write authentication info' to $_SESSION to prevent further calls to Login operation. 
				$_SESSION['SP_DATA'] = array(
					'sessionID' => (string)$this->sSessionID,
					'sessionEncoding' => (string)$this->sSessionEncoding,
					'orgID' => (string)$this->sOrganizationID,
					'sessionGenerated' => (int)time()
				);	
				session_write_close(); 
			}
		
		}
		return $bReturn; 
	}	


  /**
   * @return bool 
   *
   */
	public function fnLogout(){
		$bReturn = false; 
		$oResponse = $this->fnRequest('Logout',array()); 
		$bReturn = (strtolower((string)$oResponse->SUCCESS) == 'true')?true:false;
		$this->sSessionID = NULL; 
		$this->sSessionEncoding = NULL; 
		$this->sOrganizationID = NULL; 
		$this->sUID = NULL; 
		$this->sPWD = NULL;
		unset($_SESSION['SP_DATA']); // Dump authentication info' from $_SESSION  
		return $bReturn; 
	}


  /**
   * @var string $sList - List name to get ID for
   * @return string $sReturn - List ID || NULL 
   *
   */	
	public function fnGetListID($sListName){
		$sReturn = NULL; 
		// Get All Lists; 
		$oResponse = $this->fnRequest('GetLists',array(
			'VISIBILITY' => 1, // 1 = Shared, 0 = Private
			'LIST_TYPE' => 2
		));
		if($oResponse && !is_null($oResponse) && !empty($sListName) && !is_null($sListName)){
			$oList = $oResponse->xpath('//LIST[NAME="'.$sListName.'"]');  
			if($oList){
				$sReturn = (string)$oList[0]->ID;
				$this->sListName = $sListName; 
				$this->sListID = $sReturn; 
			}
		}
		return $sReturn; 
	}	


  /**
   * @var string $sEmail - Email address to get ID for
   * @return string $Return - Recipient ID || NULL 
   *
   */	
	public function fnGetRecipientID($sEmail){
		$sReturn = NULL; 
		if(!empty($sEmail) && !is_null($sEmail)){
			$oResponse = $this->fnRequest('SelectRecipientData',array(
				'LIST_ID' => $this->sListID,
				'EMAIL' => $sEmail,
				'RETURN_CONTACT_LISTS' => 'false'
			));	
			$sID = $oResponse->RecipientId; 
			if(!empty($sID) && !is_null($sID)) $sReturn = $sID; 
		}
		return $sReturn; 
	}	
	
  /**
   * @var string $sOperation - Valid SilverPOP operation to perform. 
   * @var mixed $mData - Either an array of parameters to pass to the request || raw, validated XML string.  
   * @return string - SimpleXML Response Object ($oResponse->Body->RESULT);  
   *
   */	
	public function fnRequest($sOperation,$mData){
		$oResponse = NULL; 
		if(in_array($sOperation,$this->aOperations)){
			$oResponse = $this->fnQuery($sOperation,$mData); 
			$oResult = $oResponse->Body->RESULT; 
			if($oResult && strtolower($oResult->SUCCESS) == 'true'){
				$oResponse = $oResult;
			}else{
				// Only echo for debug; not for production. 
				// echo '("'.$sOperation.'" Error '.$oResponse->Body->Fault->detail->error->errorid.') : '.$oResponse->Body->Fault->FaultString; 	
				// var_dump($oResponse);
			}
		
		}
		return $oResponse; 
	}
	
	
	
	// NOTE: Files are expundged from remote FTP every 14 days - SilverPOP 
	// https://kb.silverpop.com/kb/Engage/Data/SFTP/001_How_to/Setting_up_an_FTP_or_SFTP_account
	public function fnFTPGetFile($sRemotePath,$sLocalDir){
		$bReturn = false; 
		$oConn = ftp_connect($this->sFTP);
		if($oConn){
			if(ftp_login($oConn, $this->sUID, $this->sPWD)){
				$sFileName = basename($sRemotePath); 
				$oLocalFile = fopen($sLocalDir.$sFileName, "w+");
				fclose($oLocalFile); 
				if(is_dir($sLocalDir) && $oLocalFile && is_writeable($sLocalDir.$sFileName)){
					if(ftp_get($oConn, $sLocalDir.$sFileName, $sRemotePath, FTP_BINARY)){
						ftp_delete($oConn,$sRemotePath); // Remove file when grabbed. 
						$bReturn = true; 
					}else{} // Unable to get file. 	
				}else{} // Dir not writeable. 
			}else{} // Unable to login via. FTP. 
			ftp_close($oConn);
		}
		return $bReturn; 
	}
	
	
	
  /**
   * @var string $sOperation - Valid SilverPOP operation to perform. 
   * @var mixed $mData - Either an array of parameters to pass to the request || raw, validated XML string.  
   * @return string - SimpleXML Response Object ($oResponse->Body->RESULT);  
   *
   * Create the XML Payload. This method is only called from $this->fnRequest(); 
   */ 
	private function fnQuery($sOperation,$mData){
		$oResponse = NULL; 
		if(is_array($mData)){
			$aData = array(); 
			$aData['Envelope']['Body'][$sOperation] = $mData;
			$oXML = new ArrayToXML($aData); 
			$sXML = $oXML->getXML(); 
		}else{
			/*	
				$mData could be raw data because sometimes this will need to be manually constructed 
				(in the case of multiple child nodes, arrays cannot have the same index value);  
			*/
			$sXML = $mData; 
		}
		
		$aFields = array(
			'jsessionid' => isset($this->sSessionID)?$this->sSessionID:NULL,
			'xml' => $sXML
		);
		
		$oResponse = $this->fnCURL($aFields);

		
		return $oResponse;  
	}
	
	
  /**
   * @var array $aFields - Array of ADDITIONAL fields to add to the URL. ATM just sSessionEncoding... 
   * @return object - Raw SimpleXML Response Object   
   *
   * Make the request.  
   */ 
	private function fnCURL($aFields){
		$oResponse = NULL; 
		$sURL = $this->sEndPoint; 
		if(!empty($this->sSessionEncoding) && !is_null($this->sSessionEncoding))
			$sURL.= $this->sSessionEncoding; 
		
		$sFields = http_build_query(array_filter($aFields)); 
				
		$oCURL = curl_init();
		$aOptions = array(
			CURLOPT_URL => $sURL,
			CURLOPT_POST => true, 
			CURLOPT_POSTFIELDS => $sFields,
			CURLOPT_RETURNTRANSFER => true
        );		
		curl_setopt_array($oCURL,$aOptions);
		$oResponse = curl_exec($oCURL);
		if($oResponse !== false){
			$oResponse = simplexml_load_string($oResponse);
		}
		
		if(!$oResponse){
			unset($_SESSION['SP_DATA']); // Probability is the jsessionid is no longer valid. Need to re-validate. 	
		}	
		
		curl_close($oCURL); 
		return $oResponse; 
		
	}
	

	
	
} // End Class







