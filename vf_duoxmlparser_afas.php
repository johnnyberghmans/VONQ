<?php
/*
steps
1. check pw		
2. check xml - ok
3. create xml envelope - ok
4. enrich xml for specific lob
5. send xml to VONQ
6. return output
*/

//general config file
include 'config/config.php';
//general config file
include 'functions/functions.php';
//log errors file
include 'functions/logErrors.php';
//check password
include 'functions/checkPassword.php';

$expectedRequestorRS = "AFASRS";
$expectedRequestorTT = "AFASTT";
$userName = "randstadbe";
$password = "SR@a7%%fO3y9";
$clientid = "";
$reference = "";

$returnedRequestor;

//get xml
$xmlstr = file_get_contents('php://input');
//go to vonq spoofer
$callSpoofer=0;

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

		//echo $xmlWithoutNamespaces;
//
		
		
		// Create a new SimpleXMLElement object
		$source  = new SimpleXMLElement($xmlstr);
		// Register the namespace for 'srv'
		$source->registerXPathNamespace('srv', 'http://www.b-bridge.be/Enterprise/ServiceDomain/ServiceRequest');
		$source->registerXPathNamespace('com', 'http://www.b-bridge.be/Enterprise/EnterpriseTypes/Company');
		$source->registerXPathNamespace('job', 'http://www.b-bridge.be/Enterprise/JobPublications/JobPublications');
		$source->registerXPathNamespace('JobPosition', 'http://www.b-bridge.be/Enterprise/JobPublications/JobPositions');
		

		// Use XPath to find the value of <srv:Requestor>
		$result = $source->xpath('//srv:Requestor');

		// Extract the value if it exists
		$returnedRequestor = (string)$result[0];
		$reference = $returnedRequestor;
		
		//CheckRequestor of input xml with the expected requestor
		if($returnedRequestor!=$expectedRequestorRS && $returnedRequestor!=$expectedRequestorTT ){
			$error = "Requestor is not ok!";
		}
		else{
					
			$jobPositions = $source->xpath('//JobPosition:JobPosition');
			//$jobPositions = $source->xpath('//JobPosition:JobPosition');
			//echo $_SERVER['SERVER_NAME']."***";
			//search for clientID based on the requestor 
			if($returnedRequestor==$expectedRequestorRS){
				if (strpos($_SERVER['SERVER_NAME'], 'becxgwtest') !== false) {
					$clientID = "c857164418691";//clientID RS TEST
					}
				else{	
					$clientID = "4bec6d33db259";//clientID RS PROD 
				}
			}
			elseif($returnedRequestor==$expectedRequestorTT){
				if (strpos($_SERVER['SERVER_NAME'], 'becxgwtest') !== false) {
					$clientID = "b0b7755aefac3";//clientID TT TEST
					}
				else{	
					$clientID = "b079ffb8d146c";//clientID TT PROD
				}		
			}
			
			$namespaces = $source->getNamespaces(true);
			
			
			$jobDetails = $source->xpath('//JobPosition:JobPosition')[0]; // Get the first JobDetails node


			// Convert the <job:JobPositions> node to XML string if found
			if (!empty($jobPositions)) {
				
				//get envelope to put in front of original xml
				$targetXml = getEnvelope();

				// Convert the extracted JobDetails into a DOM element
				$domJobDetails = dom_import_simplexml($jobDetails);
	
				
				// Load the jobs XML and find the Jobs tag
				$jobs = new SimpleXMLElement($targetXml);
				$jobsTag = $jobs->xpath('//Jobs')[0];
				$domTarget = dom_import_simplexml($jobsTag);
				$domTargetOwner = $domTarget->ownerDocument;


				$importedJobDetails = $domTargetOwner->importNode($domJobDetails, true);
				$domTarget->appendChild($importedJobDetails);
				
				$jobs->ingoedebanen->username = 'randstadbe';
				$jobs->ingoedebanen->clientid = $clientID;
			
				$clientIDInfo = searchInArray($clientID, $clientIDs, "client");
				$isActive = "";
				$company = "";			
				$environment = "";
					
					
				if(isset($clientIDInfo)){
					$isActive = $clientIDInfo['isActive'];
					$company = $clientIDInfo['company'];				
					$environment = $clientIDInfo['environment'];
				}
				
				if ($company == ''){
					$error = 'No Randstad Group company found for given clientID. ClientID not correct.';
				}
				else{
					
					//________________________=;_________START ENRICH XML TO SEND TO VONQ_________________________________
					// Append JobDetails into th=;e Jobs tag
					//echo $jobs->asXML();
					$rsID = $reference.'-'.$jobs->job->originalXML->Jobs->JobPosition['id'];
								
					$jobs->job->originalXML->Jobs->JobPosition['status']="active";
					//$jobs->job['users']="Manager RAND-BE";
					
					
					$first_characters = $reference;
					
					//$last_characters = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle;
					$last_characters = $jobs->job->originalXML->Jobs->JobPosition['id'];
					$companyID = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->CompanyID;
					$jobs->job->title  = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription;
					
					//echo $jobs->asXML()."------------";
					//echo "****";
					include 'functions/generalEnrichmentXML.php';
							
					// Save the modified jobs XML
					//echo $jobs->asXML()."------------";
					
					//if no postedURL is passed by Siaas we add it
					$postedURL = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->PostedUrl;

					if($postedURL==""){
						$id = (string)$jobs->job->originalXML->Jobs->JobPosition['id'];
						
						if($returnedRequestor=="AFASRS"){							
							$Afas_posted_url = $Afas_posted_url_NL_RS;
							
							if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage=="FR"){
								$Afas_posted_url = $Afas_posted_url_FR_RS;	
								
							}
						}
						else{						
							$Afas_posted_url = $Afas_posted_url_NL_TT;
							
							if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage=="FR"){
								$Afas_posted_url = $Afas_posted_url_FR_TT;	
								
							}
							
						}
						$url = $Afas_posted_url . $id;										
					
						$jobs->job->originalXML->Jobs->JobPosition->JobDetails->PostedUrl = addCData($url);	
					}

					if($returnedRequestor=="AFASRS"){
						$publishTO = "RS";						
						if($doCompanyKbo){
							include 'config/rs_unitsKBO.inc';
						}
					}
					else{	
						$publishTO = "TT";		
						if($doCompanyKbo){
							include 'config/tt_unitsKBO.inc';
						}						
					}
					
					
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
					
					$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['publishto'] = $publishTO;
					$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['autorepostnow'] = "n";
					$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['repostnow'] = "n";
					
					//$jobs->job->originalXML->Jobs->JobPosition->Contact->City = 'Gent';
					$jobs->job->originalXML->Jobs->JobPosition->Contact->AddressName = 'Avenue Charles-Quint';
					$jobs->job->originalXML->Jobs->JobPosition->Contact->AddressNo = '586';
					$jobs->job->originalXML->Jobs->JobPosition->Contact->Zipcode = '1082';
					$jobs->job->originalXML->Jobs->JobPosition->Contact->City = 'Berchem-Sainte-Agathe';
					
					$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch = "Dienstensector";
					$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch['id'] = "U";
					
					$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity = ucfirst(strtolower($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity));
				
					//echo gettype($jobs); 
					//echo $jobs->asXML()."------------";
					//_________________________________END ENRICH XML TO SEND TO VONQ_________________________________
				}



			} else {
				echo "No JobPositions tag found.";
			}
		}
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
	$debugString .= "\n\nFollowing error occured \n\n".$error .	"\n\nInput xml from AFAS: \n\n".$xmlstr;
	$debugString .= "\n\noutput to SIAAS ".$output;
	echo $output;
}
else if ($vonqError!=''){
	$output = $vonqResponse;
	$result = $negativeResult;
	$debugString .= "\n\nFollowing error occured \n\n".$vonqError.	"\n\nInput xml from AFAS: \n\n".$xmlstr;
	$debugString .= "\n\noutput to SIAAS ".$output;
	echo $output;
}

$output = doLoggingToCx();

?>