<?php
//PW PRODUCTION ENVIRONMENT
if (strpos($_SERVER['SERVER_NAME'], 'becxgw.') !== false) {
$expectedPassword = "v1qFa7HMxarHUY2NzY8oIu5rQjJEy93"; // wachtwoord productie
}
//PW TEST ENVIRONMENT
else {
$expectedPassword = "ov8pUmVV74KXYJ6h2OeG8dvHzTDjDf8"; // wachtwoord test
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
	),	
);

//the enddate for Forem is maximum 6 weeks, if enddate - startdate exceeds this 6 weeks we put the enddate on this value
$forceEndDateForForem = 80;

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
$callSpoofer = 0;
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

$Afas_posted_url_NL_RS = "https://werkenbij.randstad.be/aanmaken-sollicitatie-incl-autorisatie-prs/standaard-sollicitatie-v3?VcSn=";
$Afas_posted_url_FR_RS = "https://travaillerchez.randstad.be/aanmaken-sollicitatie-incl-autorisatie-prs/standaard-sollicitatie-v3?VcSn=";
$Afas_posted_url_NL_TT = "https://werkenbij.tempo-team.be/aanmaken-sollicitatie-incl-autorisatie-prs/standaard-sollicitatie-v3?VcSn=";
$Afas_posted_url_FR_TT = "https://travaillerchez.tempo-team.be/aanmaken-sollicitatie-incl-autorisatie-prs/standaard-sollicitatie-v3?VcSn=";
?>