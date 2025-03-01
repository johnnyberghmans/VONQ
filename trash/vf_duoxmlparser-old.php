<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "https://randstadbeprof.carerix.net" || $http_origin == " https://randstadberss.carerix.net")
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

//include 'jobs.inc';
//debug parameter, if 1 the data in and out will be sent to Carerix randstadberssm as a note
$doDebug = 1;

/*
parameter to overwrite logic about the endpoints if 1 always go to spoofer despite what array says
If this parameter = 0 the logic will depend on the value of isActive in the array $clientIDs for the given clientID
if the value of isActive = 0 we will go to the spoofer, if is 1 we will go to VONQ
*/
$callSpoofer = 1;
$realsend = "";
$subjectTag = "";

$headers = getallheaders();  
$reverse = $headers['reverse'];


$endPointSpoofer = 'https://'.$_SERVER['SERVER_NAME'].'/vonq/vf_vonqspoofer.php';
$nameEndPointSpoofer = 'vonqSpoofer';
$endPointVONQ = 'https://beheer.ingoedebanen.nl/post/advanced/post.php';
$nameEndPointVONQ = 'vonq';

$debugString="";
//variable to changedates. If 1 dates in original xml feed will be changed to other format in new feed to Vonq
$doChangeDate = 1;

//variable to add CDATA. If 1 CDATA will be added to elements
$doAddCData = 1;

//variable to add Companykbo. If 1 Companykbo will be added to elements
$doCompanyKbo = 1;

//variable addjust the start and end date when enddate lies in the past
$doAddjustStartEndDate = 0;

//$vdabIDOverwrite = "";
//check on which environment test or production. for production realsend must be always 1
if (strpos($_SERVER['SERVER_NAME'], 'becxgwtest') !== false) {
    //testenvironment do nothing
	//$vdabIDOverwrite = "14806000";
	$realsend = 0;
}
else {	
	$realsend = 1;
	//$debugString .= "realsend is set to 0 because we are on production - update 21/12/2022\n\n";
}

$xmlstr = file_get_contents('php://input');
$xmlPW = "ZAqizEho";
$cx_instance = "randstadberssm";
$error = "";
$vonqError = "";

$positiveResult = "17995";
$negativeResult = "17996";
$result = "";
$rsID = "0";

//function to do look up in array
function searchInArray($lookupvalue, $array) {
   foreach ($array as $key => $val) {
	   if(isset($val['clientID'])){
		   if ($val['clientID'] == $lookupvalue) {
			 $resultSet['isActive'] = $val['isActive'];
			 $resultSet['company'] = $val['company'];
			 $resultSet['environment'] = $val['environment'];
			 return $resultSet;
			}
	   }	 
	   
	   if(isset($val['unit'])){
		   if ($val['unit'] == $lookupvalue) {
			 $resultSet['kboCompany'] = $val['kboCompany'];
			 $resultSet['kboOffice'] = $val['kboOffice'];
			 $resultSet['vdabID'] = $val['vdabID'];
			 return $resultSet;
		   }
	   }
   }
   return null;
}

//array with allowed clientIDs
$clientIDs = array(
	array(
		'clientID' => '4bec6d33db259',
		'isActive' => '1',
		'company' => 'RS',
		'description' => 'Randstad Group Belgium nv',
		'environment' => 'Prod'
	),
	array(
		'clientID' => 'c857164418691',
		'isActive' => '1',
		'company' => 'RS',
		'description' => 'Randstad Group Belgium nv (NPE)',
		'environment' => 'Test'
	),
	array(
		'clientID' => 'b079ffb8d146c',
		'isActive' => '1',
		'company' => 'TT',
		'description' => 'TempoTeam Belgium nv',
		'environment' => 'Prod'
	),
	array(
		'clientID' => 'b0b7755aefac3',
		'isActive' => '1',
		'company' => 'TT',
		'description' => 'TempoTeam Belgium nv (npe)',
		'environment' => 'Test'
	),	
	array(
		'clientID' => 'b94cfe9951c3b',
		'isActive' => '1',
		'company' => 'RSP',
		'description' => 'Randstad Professionals',
		'environment' => 'Prod'
	),
	array(
		'clientID' => '553c8826ef69f',
		'isActive' => '1',
		'company' => 'RSP',
		'description' => 'Randstad Professionals',
		'environment' => 'Test'
	)	
);

function changeDateFormat($vDate){
	global $doChangeDate;
	if($doChangeDate){
		$vDate = str_replace("/","-",$vDate);
	}
	return $vDate;
}

function addCData($element){
	global $doAddCData;
	if($doAddCData){
		//check if element already has CDATA element. If not add the CDATA, if yes do no manipulations
		if (strpos($element, "<![CDATA[") === false) {
			$element = "<![CDATA[".str_replace('&lt;','<',$element)."]]>";
		}
	} 
	
	return $element;
}

//this function is not used anymore, needs to be used if xml to Carerix gives errors
function xml_entities($string) {
    return strtr(
        $string, 
        array(
            "<" => "&lt;",
            ">" => "&gt;",
            '"' => "&quot;",
            "'" => "&apos;",
            "&" => "&amp;",
        )
    );
}

//replace CDATA in notes send to Carerix because we cannot nest CDATA in xml string
function cdata_replace($string) {
	$string = str_replace('<![CDATA[','&lt;![CDATA[',$string);
	$string = str_replace(']]>',']]&gt;',$string);
	return $string;
}

//check if xml is valid document
function _isValidXML($content) {
     $content = trim($content);
    if (empty($content)) {
        return false;
    }
    if (stripos($content, '<!DOCTYPE html>') !== false) {
        return false;
    }
    libxml_use_internal_errors(true);
    simplexml_load_string($content);
    $errors = libxml_get_errors();          
    libxml_clear_errors();  
    return empty($errors);
}
	
function json_validator($data) {
	if (!empty($data)) {
		return is_string($data) && 
		  is_array(json_decode($data, true)) ? true : false;
	}
	return false;
}

function xmlInterfaceSave($xml){
	global $cx_instance, $xmlPW;
	$url = 'https://'.$cx_instance.'.carerix.net/cgi-bin/WebObjects/'.$cx_instance.'Web.woa/wa/save';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml', 'x-cx-pwd: '.$xmlPW));
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	
	if ($output === null || $output == FALSE || $output == '') {
        $error = curl_error($ch);
        return false;
    } 
	else {
       return $output;
       //fclose($ch);
    }
	
}

function vonqPoster($xml){
	//echo $xml;
	global $error, $endPoint;
	
	$url = $endPoint;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);  


	$output = curl_exec($ch);

	if ($output === null || $output == FALSE || $output == '') {
        $error = curl_error($ch);
        return false;
    } 
	else {
       return $output;
       //fclose($ch);
    }
}

//This is for testing purposes. If the enddate of the given xml is lower than the currentdate we will create a new start and enddate. So we can test with old publications xml
function checkEndDate($endDate){
	global $doAddjustStartEndDate;
	//echo $doAddjustStartEndDate;
	if($doAddjustStartEndDate){
		$arrEndDate = explode("-", $endDate);
		$formattedEndDate = $arrEndDate[2].'-'.$arrEndDate[1].'-'.$arrEndDate[0];
		
		if (date("Y-m-d") > $formattedEndDate){
			return true;
		}else{
			return false;		
		} 
	}
}

function sanitizeCity($locationCity){ 
	//sanitize locationCity ingoedebanen
	//$locationCity = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity;
	//remove all numbers
	$locationCity = preg_replace('#[0-9]#', '', $locationCity);
	//remove text between brackets
	$locationCity = preg_replace( '~\(.*\)~' , "", $locationCity); 
	//remove dot at the end 
	$locationCity = rtrim($locationCity,'.'); 
	//trim
	$locationCity = trim($locationCity);
	return $locationCity;
}

if(!_isValidXML($xmlstr)){
   $error = "No valid xml!";
}
else{
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
			
			$clientIDInfo = searchInArray($clientID, $clientIDs);
			$isActive = $clientIDInfo['isActive'];
			$company = $clientIDInfo['company'];
			
			$environment = $clientIDInfo['environment'];
						
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
					//include 'rs_offices.inc';
					//include 'rs_units.inc';
						if($doCompanyKbo){
							include 'rs_unitsKBO.inc';
						}
					$reference = "DUORS";	
				}
				else if($company == "TT") {
					$first_characters = substr($rsID, 0, 2);
					$last_characters = substr($rsID, 2);
					//include 'tt_offices.inc';
					//include 'tt_units.inc';	
						if($doCompanyKbo){
							include 'tt_unitsKBO.inc';
						}
					$reference = "DUOTT";
				}
				else if($company == "RSP") {
					$first_characters = substr($rsID, 0, 3);
					$last_characters = substr($rsID, 3);
					//include 'tt_offices.inc';
					//include 'tt_units.inc';	
						if($doCompanyKbo){
							include 'rsp_unitsKBO.inc';
						}
					$reference = "CXRSP";
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

if ($error==''){	

	$id = (string)$jobs->job['id'];
	$newRef =  $reference.'-'.$last_characters;	

	$jobs->job->originalXML->Jobs->JobPosition->JobDetails->reference = $newRef;
	$jobs->job['id'] = $newRef;
	
	
	if($jobs->job->title && ($jobs->job->title != '') ) {
		$jobs->job->title= addCData($jobs->job->title);
		$debugString .= "jobs->job->title is not empty we don't add the tag \n\n";
	}
	else {
		$jobs->job->title= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription);
		$debugString .= "jobs->job->title is empty or doesnot exists. We create the title with info from the jobdescription tag\n\n";
	}


	$jobs->job->reference =  $newRef;
	$jobs->job->internalDUOReference =  $reference.'-'.$jobs->job->originalXML->Jobs->JobPosition->VacancyNumber;

	//only if unitcode is present
	if(isset($jobs->job->originalXML->Jobs->JobPosition->Contact->Unitcode) ){
		
		if($doCompanyKbo){
			$unitcode = (string)$jobs->job->originalXML->Jobs->JobPosition->Contact->Unitcode;
			$unitcode =  $unitcode;
	
			$clientIDInfo = searchInArray($unitcode, $unitIDs);

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
			if($environment == 'Test'){
				$doAddjustStartEndDate = 1;
			}
				
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
		//change date formats replace / by -
		if(isset($jobs->job->originalXML->Jobs->JobPosition->Channels->Channel) ){
			$startDate = $jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['startdate'];
			$endDate = $jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['enddate'];
			
			$startDate = changeDateFormat($startDate); 
			$endDate = changeDateFormat($endDate); 
			
			if (checkEndDate($endDate)){	
				$newStartDate = date("d-m-Y");
				$newEndDate = date("d-m-Y", strtotime('+1 month'));
		
				$startDate = $newStartDate; 
				$endDate = $newEndDate; 
				
				$debugString .= "Variable doAddjustStartEndDate = " .$doAddjustStartEndDate. " New startdate (".$newStartDate.") and enddate (".$newEndDate.") is added to original xml because the original enddate (".$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['enddate'].") was in the past.\n\n";
			}
			else{
				$debugString .= "Variable doAddjustStartEndDate = " .$doAddjustStartEndDate. ". No changes in start and enddate were added to original xml. Original enddate : ".$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['enddate']." \n\n";
				
			}

			
			$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['startdate'] = $startDate;
			$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['enddate'] = $endDate;
		}


		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle= ltrim($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle, '0 ');
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobCompanyProfile= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobCompanyProfile);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->CompanyName= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->CompanyName);
		
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->EmploymentSite= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->EmploymentSite);
		/*
		//sanitize locationCity
		$locationCity = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity;
		//remove all numbers
		$locationCity = preg_replace('#[0-9]#', '', $locationCity);
		//remove text between brackets
		$locationCity = preg_replace( '~\(.*\)~' , "", $locationCity); 
		//trim
		$locationCity = trim($locationCity);
		*/ 
		
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity = sanitizeCity($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLocation->LocationCity);
		
		$jobs->job->originalXML->Jobs->JobPosition->Contact->BranchName= addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->BranchName);
		$jobs->job->originalXML->Jobs->JobPosition->Contact->BranchEmail= $jobs->job->originalXML->Jobs->JobPosition->Contact->BranchEmail;
		
		$jobs->job->originalXML->Jobs->JobPosition->Contact->BranchPhone= addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->BranchPhone);
		$jobs->job->originalXML->Jobs->JobPosition->Contact->Phone= addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->Phone);
		
		if($jobs->job->originalXML->Jobs->JobPosition->Contact->Name ==''){
			$jobs->job->originalXML->Jobs->JobPosition->Contact->Name = addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->BranchName);
			$debugString .= "JobPosition->Contact->Name is empty we fill in the JobPosition->Contact->BranchName \n\n";	
		}else{
			$jobs->job->originalXML->Jobs->JobPosition->Contact->Name = addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->Name);
		}
		
		$jobs->job->originalXML->Jobs->JobPosition->Contact->AddressName= addCData($jobs->job->originalXML->Jobs->JobPosition->Contact->AddressName);
		$jobs->job->originalXML->Jobs->JobPosition->JobProfileDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobProfileDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobContentDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobContentDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobTasksDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobTasksDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobExperienceDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobExperienceDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobLanguagesDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobLanguagesDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobEducationDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobEducationDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobContentDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobContentDescription);
		
		//TT dienstencheques for company 0467127056 we replace contractype to z
		if($companyID=='0467127056'){
			$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobContractType = 'Z';//addCData('Z');
			$debugString .= "companyID=='0467127056' we assign JobContractType to 'Z' \n\n";
		}
		
		if(!isset($jobs->job->originalXML->Jobs->JobPosition->VDABArea)){
			$jobs->job->originalXML->Jobs->JobPosition->addChild('VDABArea');
		}
		//echo "**".$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]');
		//check for tag SERV competence, if it doesnot exists add it
		if (!$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]')){
			$debugString .= 'Compence tag SERV competence not found so we will add it. \n\n';
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->addChild('Competency')->addAttribute('name', 'SERV competence');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]')[0]->addAttribute('required', 'true');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]')[0]->addChild('CompetencyId')->addAttribute('id', '16856');
		}

		//check for tag Study Code, if it doesnot exists add it
		if (!$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Study Code"]')){
			$debugString .= 'Compence tag Study Code not found so we will add it. \n\n';
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->addChild('Competency')->addAttribute('name', 'Study Code');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Study Code"]')[0]->addAttribute('required', 'true');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Study Code"]')[0]->addChild('CompetencyId')->addAttribute('id', 'A');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Study Code"]')[0]->addChild('TaxonomyId')->addAttribute('id', 'StudyCodes 2.0');
		}

		//check for tag Drivers License, if it doesnot exists add it
		if (!$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Drivers License"]')){
			$debugString .= 'Compence tag Drivers License not found so we will add it. \n\n';
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->addChild('Competency')->addAttribute('name', 'Drivers License');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Drivers License"]')[0]->addChild('CompetencyId')->addAttribute('id', '');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Drivers License"]')[0]->addChild('TaxonomyId')->addAttribute('id', '91/439/EEC');
		}
		
		//check for tag Language, if it doesnot exists add it
		if (!$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')){
			$debugString .= 'Compence tag Language not found so we will add it. \n\n';
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->addChild('Competency')->addAttribute('name', 'Language');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')[0]->addChild('CompetencyId')->addAttribute('id', $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage);
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')[0]->addChild('TaxonomyId')->addAttribute('id', 'ISO 639-1');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')[0]->addChild('CompetencyEvidence')->addChild('NumericValue')->addAttribute('minValue', '1');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')[0]->CompetencyEvidence->NumericValue->addAttribute('maxValue', '4');
			$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="Language"]')[0]->CompetencyEvidence->NumericValue=3;
		}	

		
		//VONQ expects the tag JobWorkingHour, if it is not send by the original xml from DUO we add it as an empty tag.
		if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobWorkingHours->JobWorkingHour == ''){
			$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobWorkingHours->JobWorkingHour = "";
			$debugString .= "Tag JobWorkingHour is added to original xml\n\n";
		}
			
	}

	if ($error==''){	
	
		//if callspoofer = 1 send to spoofer
		if($callSpoofer==1){
			$endPoint = $endPointSpoofer;
			$nameEndPoint = $nameEndPointSpoofer;
		}
	
		$vonqResponse = vonqPoster(html_entity_decode($jobs->asXML()));
			
		if ($error==''){	
			$debugString .= "\n\nResponse from ".$nameEndPoint."\n\n".$vonqResponse;
			$debugString .= "\n\nAugmented xml send to ".$nameEndPoint."\n\n".$jobs->asXML();
			$debugString .= "\n\nInput xml from DUO: \n\n".$xmlstr;
			
			/*
			echo "<html>";
			echo "<body>";
			echo "<h1>Original xml</h1>";
			echo "<textarea rows='10' cols='250'>".$xmlstr."</textarea><br/>";
			echo "<h1>New xml send to Vonq</h1>";
			echo "<textarea rows='10' cols='250'>".$jobs->asXML()."</textarea><br/>";
			echo "<h1>Vonq Response</h1>";
			echo "<textarea rows='10' cols='250'>".vonqPoster($jobs->asXML())."</textarea><br/>";
			echo "</body>";
			echo "</html>";
			*/
			$result = $positiveResult;
			if (json_validator($vonqResponse)){
				$vonqResponseJson = json_decode($vonqResponse,true);
				
				//error from VONQ
				if( isset( $vonqResponseJson['failed'] ) ){
					   $vonqError = $vonqResponse;
					}
				//success from VONQ
				else{
					echo $vonqResponse;
				}
			}
			//error invalid json from VONQ
			else{
				$error = '{"failed": {"all": "vonqproxy: invalid json returned"}}';
			}
			
		}				
	}				
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

$cDataStart = "<![CDATA[";
$cDataEnd = "]]>";
if ($doDebug){
		$vXML = '<CRToDo>
			<name>VONQ LOG : '.$rsID.' - '.$nameEndPoint.' - ' . $_SERVER['SERVER_NAME'] . $subjectTag .' </name>
			<subject>VONQ LOG : '.$rsID.' - '.$nameEndPoint.' - ' . $_SERVER['SERVER_NAME'] . $subjectTag .' </subject>
			<notes>'.$cDataStart.cdata_replace($debugString).$cDataEnd.'</notes>
			<todoTypeKey>4</todoTypeKey>
			<owner><CRUser id="1"></CRUser></owner>
			<toActivityTypeNode><CRDataNode id="17994"></CRDataNode></toActivityTypeNode>
			<toStatusNode><CRDataNode id="'.$result.'"></CRDataNode></toStatusNode>
			</CRToDo>';
		//echo "*******************************";
		//echo $vXML;
		//echo "*******************************";
		$output = xmlInterfaceSave($vXML);
		//echo "***".$output;	
	}
?>