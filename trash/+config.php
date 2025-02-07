<?php

//PRODUCTION ENVIRONMENT
if (strpos($_SERVER['SERVER_NAME'], 'becxgw.') !== false) {
// Basis validatie van het wachtwoord
$expectedPassword = "securePasswordProd"; // Stel jouw wachtwoord hier in
}
else {
$expectedPassword = "securePassword"; // Stel jouw wachtwoord hier in
}	
//log errors file
//include 'checkPassword.php';
	
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
	),	
);

//the enddate for Forem is maximum 6 weeks, if enddate - startdate exceeds this 6 weeks we put the enddate on this value
$forceEndDateForForem = 40;

$debugString="";
//variable to changedates. If 1 dates in original xml feed will be changed to other format in new feed to Vonq
$doChangeDate = 1;

//variable to add CDATA. If 1 CDATA will be added to elements
$doAddCData = 1;

//variable to add Companykbo. If 1 Companykbo will be added to elements
$doCompanyKbo = 1;


$endPointSpoofer = 'https://'.$_SERVER['SERVER_NAME'].'/vonq/vf_vonqspoofer.php';
$nameEndPointSpoofer = 'vonqSpoofer';
$endPointVONQ = 'https://beheer.ingoedebanen.nl/post/advanced/post.php';
$nameEndPointVONQ = 'vonq';


//allow to open iframe form CX 
if(isset($_SERVER['HTTP_ORIGIN'])){
	$http_origin = $_SERVER['HTTP_ORIGIN'];
	if ($http_origin == "https://randstadbeprof.carerix.net" || $http_origin == " https://randstadberss.carerix.net"){  
		header("Access-Control-Allow-Origin: $http_origin");
	}
}

$headers = getallheaders();
$reverse = 0;  
if(isset($headers['reverse'])){
	$reverse = $headers['reverse'];
}

//debug parameter, if 1 the data in and out will be sent to Carerix randstadberssm as a note
$doDebug = 1;
$doLogInputFromSphere = 0;
/*
parameter to overwrite logic about the endpoints if 1 always go to spoofer despite what array says
If this parameter = 0 the logic will depend on the value of isActive in the array $clientIDs for the given clientID
if the value of isActive = 0 we will go to the spoofer, if is 1 we will go to VONQ
*/
$callSpoofer = 1;
$realsend = "";
$subjectTag = "";

//check on which environment test or production. for production realsend must be always 1

//PRODUCTION ENVIRONMENT
if (strpos($_SERVER['SERVER_NAME'], 'becxgw.') !== false) {
    //testenvironment do nothing
	//$vdabIDOverwrite = "14806000";
	$realsend = 0;
	
	//variable addjust the start and end date when enddate lies in the past, On test this variable = 1 for testing, on production it is 0
	$doAddjustStartEndDate = 0;
}
//TEST ENVIRONMENT
else {	
	$realsend =0;
	//$debugString .= "realsend is set to 0 because we are on production - update 21/12/2022\n\n";
	
	//variable addjust the start and end date when enddate lies in the past, On test this variable = 1 for testing, on production it is 0
	$doAddjustStartEndDate = 1;
}
//contains envelope where every xml needs to start with for sending to VONQ
function getEnvelope() {
	global $expectedRequestor, $userName, $password, $clientid;
	
	// Target envelope XML to merge into
	$targetXml = <<<XML
	<?xml version="1.0" encoding="UTF-8"?>
	<jobs>
		<ingoedebanen>
			<username>$userName</username>
			<password>$password</password>
			<clientid>$clientid</clientid>
		</ingoedebanen>
		<job id="">
			<action>addupdate</action>
			<reference/>
			<title></title>
			<description/>
			<requirements/>
			<channels/>
			<originalXML>
				<Jobs>
				</Jobs>
			</originalXML>
		</job>
	</jobs>
	XML;

    return $targetXml;
}

//general function to check if xml is valid 
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
	   
	   if(isset($val['newDUOID'])){
		   if ($val['newDUOID'] == $lookupvalue) {
			 $resultSet['rascoID'] = $val['rascoID'];
			 $resultSet['iscoID'] = $val['iscoID'];
			 return $resultSet;
		   }
	   }
   }
   return null;
}
//function to change the dateformat from dd/mm/yyyy to dd-mm-yyyy
function changeDateFormat($vDate){
	global $doChangeDate;
	if($doChangeDate){
		$vDate = str_replace("/","-",$vDate);
	}
	return $vDate;
}

//function to add CDATA to xml
function addCData($element){
	global $doAddCData;
	if($doAddCData){
		
		if($element!=''){
			//check if element already has CDATA element. If not add the CDATA, if yes do no manipulations
			if (strpos($element, "<![CDATA[") === false) {
				$element = "<![CDATA[".str_replace('&lt;','<',$element)."]]>";
			}
		}
	} 
	
	return $element;
}

//replace CDATA in notes send to Carerix because we cannot nest CDATA in xml string
function cdata_replace($string) {
	$string = str_replace('<![CDATA[','&lt;![CDATA[',$string);
	$string = str_replace(']]>',']]&gt;',$string);
	return $string;
}

//validate json function
function json_validator($data) {
	if (!empty($data)) {
		return is_string($data) && 
		  is_array(json_decode($data, true)) ? true : false;
	}
	return false;
}

//function to post jobposting to VONQ
function vonqPoster($xml){
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
	//echo "+++".$doAddjustStartEndDate;
	if($doAddjustStartEndDate){

		$formattedEndDate = DateTime::createFromFormat('d-m-Y', $endDate);
		$currentDate = new DateTime();
		
		if ($currentDate > $formattedEndDate){
			return true;
		}else{
			return false;		
		} 
	}
}

//For Forem publications the enddate must be not longer than the startdate + 40 days
function adjustEndDate($startDate, $endDate){
	global $forceEndDateForForem, $debugString;
	//echo $forceEndDateForForem;
	if($forceEndDateForForem){	
		$formattedStartDate = DateTime::createFromFormat('d-m-Y', $startDate);
				
		$endDate = DateTime::createFromFormat('d-m-Y', $endDate);
		$maxEndDate = DateTime::createFromFormat('d-m-Y', $startDate);
		$maxEndDate->modify('+'.$forceEndDateForForem.' days');
			
		if ($endDate > $maxEndDate){
			$debugString .= "Enddate is adjusted for this medium. Endate was = " .$endDate->format('d-m-Y'). " and is adjusted to ".$maxEndDate->format('d-m-Y')." \n\n";
			//		echo 	"change enddate to ".$maxEndDate->format('d-m-Y');
			return $maxEndDate->format('d-m-Y');
		}else{	
			//	echo 	"leave enddate as is ".$endDate->format('d-m-Y');
			return $endDate->format('d-m-Y');		
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


?>