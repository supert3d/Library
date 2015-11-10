<?php
/**
  * @author Tony Collings <tony@tonycollings.com>
  * @license GPL
  * @version 1.0  
  * @version GIT: $Id$ In development (VERY much!).
  */


/**
 * Based on v17.7 of the Xinet JSON API. 
 *
 * Example : 1
 * $aDirInfo = $oPortal->fnRequest('showdirinfo',array(
 * 		'fileid' => [num]
 * ));  
 *
 */

class Xinet {

	public $sXinetInstance = ''; 
	private $sEndPoint = 'webnative/portalDI?';
	private $sUID = NULL;
	private $sPWD = NULL; 
	private $aValidActions = array(
		'version',
		'showvols',
		'showusersettings',
		'showkywdperms',
		'showbaskbtns',
		'showiccusm',
		'clearbasket',
		'showdirinfo',
		'fileinfo',
		'showbasket',
		'addbasket',
		'removebasket',
		'upload',
		'getorderimage',
		'streamfile',
		'submitkywd',
		'filemgr',
		'annotations',
		'saveannotations',
		'browse',
		'presearch',
		'search'
	); 
	
	
	public function __construct(){
		
	}
	
	public function __destruct(){
		
	}
	
	public function fnLogin($sUID,$sPWD){
		// Arbitrary just to check for API response. 
		$aReturn = NULL;  
		$this->sUID = $sUID; 
		$this->sPWD = $sPWD; 
		$aResponse = $this->fnRequest('showusersettings',array()); 	
		if(isset($aResponse['MAILTO'])){
			$aReturn = $aResponse;
		}else{
			$this->sUID = NULL; 
			$this->sPWD = NULL; 	
		}
			
		return $aReturn; 
	}
	
	// Valid $sReturnType = json || binary (for assets). 
	public function fnRequest($sAction,$aParams=array(),$sReturnType='json'){
		$mResponse = NULL; 
		$aParams = array_filter($aParams)+array('action'=>$sAction);
		$sURL = $this->sXinetInstance; 
		$sURL .= $this->sEndPoint; 
		$sURL .= http_build_query($aParams);
		$oCURL = curl_init();
		$aOptions = array(
			CURLOPT_URL => $sURL,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC, //CURLAUTH_ANY,
			CURLOPT_USERPWD => $this->sUID.":".$this->sPWD
		);
		if($sReturnType == 'binary')
			$aOptions[CURLOPT_BINARYTRANSFER] = true; 
			
		curl_setopt_array($oCURL,$aOptions);
		$oResponse = curl_exec($oCURL);
		if(curl_errno($oCURL)){
			echo 'Curl error: ' . curl_error($oCURL);
		}else{
			switch($sReturnType){
				case 'json': 
					$mResponse = json_decode($oResponse,true);
				break;
				case 'binary': 
					$mResponse = 'data:image/jpg;base64,' . base64_encode($oResponse);
				break; 	
			}
		}
		curl_close($oCURL);
		return $mResponse; 
	
	}
	
	

}