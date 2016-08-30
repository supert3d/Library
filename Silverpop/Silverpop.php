<?php
/**
  * @author Tony Collings <tony@tonycollings.com>
  * @license GPL
  * @version 1.0  
  * @version GIT: $Id$ In development.
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
		$oResponse = $this->fnRequest('Login',array(
			'USERNAME' => $sUID,
			'PASSWORD' => $sPWD
		)); 
		$bReturn = (strtolower((string)$oResponse->SUCCESS) == 'true')?true:false;
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
		if(!empty($sListName) && !is_null($sListName)){
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
				if($sOperation == 'Login'){				
					$this->sSessionID = $oResult->SESSIONID; 	
					$this->sSessionEncoding = $oResult->SESSION_ENCODING; 	
					$this->sOrganizationID = $oResult->ORGANIZATION_ID; 	
				}
			}else{
				// Only echo for debug; not for production. 
				// echo '("'.$sOperation.'" Error '.$oResponse->Body->Fault->detail->error->errorid.') : '.$oResponse->Body->Fault->FaultString; 	
			}
		}
		return $oResponse; 
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
		curl_close($oCURL); 
		return $oResponse; 
		
	}
	

	
	
} // End Class







