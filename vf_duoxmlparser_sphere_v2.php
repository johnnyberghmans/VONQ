<?php
/*
For input xml from Sphere we do not need to add the envelope because it is already in the xml. 
We do nieed to check the envelop if it contains the right clientID
*/

//general config file
include 'config/config.php';
//general config file
include 'functions/functions.php';
//log errors file
include 'functions/logErrors.php';
//check password
include 'functions/checkPassword.php';


//get xml
$xmlstr = file_get_contents('php://input');

//log input from Spere if variable is 1
if ($doLogInputFromSphere){
error_log("\nXML from Sphere = ". date("Y-m-d H:i:s") ."\n" . $xmlstr . "\n", 3, "sphere.log");
}

if ($error==''){
	//CheckXml input
	if(!_isValidXML($xmlstr)){
	   $error = "No valid xml!";
	}
	else{
		// Load the target XML and find the Jobs tag
		$jobs = new SimpleXMLElement($xmlstr);
		
		
		//check valid headers are sent required username, password and clientid
		 if(isset($jobs->ingoedebanen->username) && 
			isset($jobs->ingoedebanen->password) && 
			isset($jobs->ingoedebanen->clientid) ){
				
				$jobs->ingoedebanen->username = 'randstadbe';
				$clientID = $jobs->ingoedebanen->clientid;
				
				/*
				if($clientID == '4bec6d33db259'){
					$debugString .= "ClientID of production 4bec6d33db259 will be temporary replaced by clientID of test c857164418691. \n\n";
					$clientID ='c857164418691';
					
					$jobs->ingoedebanen->clientid =$clientID;
				}*/
				
				$clientIDInfo = searchInArray($clientID, $clientIDs, "client");
				$isActive = "";
				$company = "";			
				$environment = "";
					
				if(isset($clientIDInfo)){
					$isActive = $clientIDInfo['isActive'];
					$company = $clientIDInfo['company'];
					
					$environment = $clientIDInfo['environment'];
				}
				
				//if no company found for given clientID means that clientID is not correct show error
				if ($company == ''){
					$error = 'No Randstad Group company found for given clientID. ClientID not correct.';
				
				}
				else{
					$rsID = $jobs->job['id'];
					
					$reference = "";
		
					if($company == "RS") {
						$first_characters = substr($rsID, 0, 2);
						$last_characters = substr($rsID, 2);
						include 'config/rasco_functions_mapping.inc';
						//include 'rs_offices.inc';
						//include 'rs_units.inc';
							if($doCompanyKbo){
								include 'config/rs_unitsKBO.inc';
							}
						$reference = "DUORS";	
						//$prefixPostedURL = "https://www.randstad.be/job/";	
					}
					else if($company == "TT") {
						$first_characters = substr($rsID, 0, 2);
						$last_characters = substr($rsID, 2);
						include 'config/rasco_functions_mapping.inc';
						//include 'tt_offices.inc';
						//include 'tt_units.inc';	
							if($doCompanyKbo){
								include 'config/tt_unitsKBO.inc';
							}
						$reference = "DUOTT";
						//$prefixPostedURL = "https://www.tempo-team.be/job/";	
					}
					else if($company == "RSP") {
						$first_characters = substr($rsID, 0, 3);
						$last_characters = substr($rsID, 3);
						//include 'tt_offices.inc';
						//include 'tt_units.inc';	
							if($doCompanyKbo){
								include 'config/rsp_unitsKBO.inc';
							}
						$reference = "CXRSP";
						//$prefixPostedURL = "https://www.randstad.be/job/";	
					}
					else {
						$error = 'No Randstad Group company found for given clientID: '.$clientID;
					}
					
					if($first_characters != $company){
						$error = 'Wrong jobID '.$rsID .' sent for given clientID ' . $clientID .'.';
					}
					
					if(strtolower($environment) == 'test'){
						$realsend = 0;
					}
					
					if($reverse == 1){
						if($realsend == 0){
							$realsend = 1;
						}
						else if($realsend == 1){
							$realsend = 0;
						}
					}
					
					if($isActive == '0' || $realsend == 0){
						$debugString .= "Will not be sent to VONQ but to Spoofer: isActive = ".$isActive. " - realsend = ".$realsend." - reverse = " .$reverse ." It will only be sent to VONQ if isActive=1 AND realsend=1\n\n";
						$endPoint = $endPointSpoofer; 
						$nameEndPoint = $nameEndPointSpoofer;
						$callSpoofer = 1;
					}
					else{
						$debugString .= "Will be sent to REAL VONQ:  isActive = ".$isActive. " - realsend = ".$realsend." - reverse = " .$reverse ."\n\n";
						$endPoint = $endPointVONQ;
						$nameEndPoint = $nameEndPointVONQ;
						$callSpoofer = 0;
					}
					
				}
		 } 
		else {
			$error = 'no valid headers sent. Expected username, password or clientid is missing.';
		}
	}

	//If no errors found start with enrichment of xml data
	if ($error==''){	

			
		//only if unitcode is present
		if(isset($jobs->job->originalXML->Jobs->JobPosition->Contact->Unitcode) ){
			
			if($doCompanyKbo){
				$unitcode = (string)$jobs->job->originalXML->Jobs->JobPosition->Contact->Unitcode;
				$unitcode =  $unitcode;
		
				$clientIDInfo = searchInArray($unitcode, $unitIDs, "kbo");

					
				if(isset($clientIDInfo)){				
					if (count((array)$clientIDInfo)==0){
						$error = 'Unitcode '.$unitcode.' not found in mapping file!';
						$subjectTag = ' - #mappingError - Unitcode not found#';
						/*
						//if not found we do look up for first unit with all fields filled in
						foreach ($unitIDs as $key => $val) {			
						   if ($val['kboCompany']!='' && $val['kboOffice']!='' && $val['vdabID']!='') {
								$kboCompany = $val['kboCompany'];
								$kboOffice = $val['kboOffice'];
								$vdabID = $val['vdabID'];
								$debugString .= "unit ".$unitcode." not found. We will use random first entry unit: ".$val['unit']."\n\n";
								
								break;
						   }	
						}		
						*/
					}
					else{
						$kboCompany = $clientIDInfo['kboCompany'];
						$kboOffice = $clientIDInfo['kboOffice'];
						$vdabID = $clientIDInfo['vdabID'];
					}				
					
					//IF VDAB PUBLICATION WE NEED A VDABID IF NOT FOUND SHOW ERROR
					if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage == 'NL'){				
						if($vdabID == '0'){
							$error = 'Unitcode '.$unitcode.' has no VDAB id in mapping file! Because it is a VDAB publication (language NL) we cannot send.';
							$subjectTag = ' - #mappingError - VDABID not found#';
						}			
					}
					//IF FOREM PUBLICATION WE NEED A kboCompany IF NOT FOUND SHOW ERROR
					else if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage == 'FR'){
						if($kboCompany == '0' ){
							$error = 'Unitcode '.$unitcode.' has no KBOCompany id in mapping file! Because it is a FOREM publication (language FR) we cannot send.';
							$subjectTag = ' - #mappingError - KBO not found#';
						}
					}
					
					/*if($kboCompany == '0' || $kboOffice == '0' || $vdabID == '0'){
						$debugString = "no kbo or vdab numbers found for unit ".$unitcode.". will use default setting. \n\n ";	
					}*/
					
						
					//for test environment we adjust start and enddate if enddate is in the past
					//if($environment == 'Test'){
					//	$doAddjustStartEndDate = 1;
					//}
						
					$office = "";//$arrUnit[$unitcode];
					//$kbo = $arrOffice[$office];

					$jobs->job->originalXML->Jobs->JobPosition->Contact->Officecode = $office;
					$jobs->job->originalXML->Jobs->JobPosition->Contact->Officekbo = $kboOffice;
					$jobs->job->originalXML->Jobs->JobPosition->Contact->Companykbo = $kboCompany;
					
					if($kboCompany!='' && $kboOffice!=''){				
						$jobs->job->originalXML->Jobs->JobPosition->Contact->foremofficekbo = $kboCompany . "#" . $kboOffice;
					}
					else{
						$jobs->job->originalXML->Jobs->JobPosition->Contact->foremofficekbo = "";
					}
					
					
					//on test we always need to send a fix vdabid
					/*if($vdabIDOverwrite!=''){
						$vdabID = $vdabIDOverwrite;
					}*/
					
					
					$jobs->job->originalXML->Jobs->JobPosition->Contact->vdabID = $vdabID;
					
				}
				else{
					$error = 'no unitcode found';
				}
				
			} 
		
		
		}	
		else {
			$error = 'no unitcode found';
		}
		
		$companyID = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->CompanyID;
		if($companyID==''){
			$error = 'CompanyID not found in xml. This is required for publishing.';
			$subjectTag = ' - #mappingError - companyID not found#';
		}
		
		
		if ($error==''){	
			//_________________________________START ENRICH XML TO SEND TO VONQ_________________________________
			include 'functions/generalEnrichmentXML.php';
				 
			//echo $jobs->asXML();
			//_________________________________END ENRICH XML TO SEND TO VONQ_________________________________
		
		}
		// Save the modified target XML
		//	echo $jobs->asXML();

	}


	//_________________________________START POST TO VONQ_________________________________
	//Call Spoofer		
	if ($error==''){	
		include 'functions/callVonq.php';
	}				
	//_________________________________END POST TO VONQ_________________________________

}

if ($error!=''){	
	$output = '{"failed": {"all": "vonqproxy: '.$error.'"}}';
	
	$result = $negativeResult;
	$debugString .= "\n\nFollowing error occured \n\n".$error .	"\n\nInput xml from DUO: \n\n".$xmlstr;
	$debugString .= "\n\noutput to Sphere ".$output;
	echo $output;
}
else if ($vonqError!=''){
	$output = $vonqResponse;
	$result = $negativeResult;
	$debugString .= "\n\nFollowing error occured \n\n".$vonqError.	"\n\nInput xml from DUO: \n\n".$xmlstr;
	$debugString .= "\n\noutput to Sphere ".$output;
	echo $output;
}


$output = doLoggingToCx();
?>