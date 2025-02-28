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

$expectedRequestorRS = "CXRSP";
//$expectedRequestorTT = "AFASTT";
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
			
			
			// Extract the <job:JobPositions> node
			//$jobPositions = $source->xpath('//JobPosition');
			$jobPositions = $source->xpath('//JobPosition:JobPosition');
			
			//search for clientID based on the requestor 
			if($returnedRequestor==$expectedRequestorRS){
				if (strpos($_SERVER['SERVER_NAME'], 'becxgwtest') !== false || strpos($_SERVER['SERVER_NAME'], 'localhost') !== false) {
					$clientID = "553c8826ef69f";//clientID RSP TEST
					}
				else{	
					$clientID = "b94cfe9951c3b";//clientID RSP PROD 
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
				$dom = new DOMDocument();
				$domJobDetails = $dom->importNode($domJobDetails, true);

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

					//for Cx we combine jobDescription an jobTasksDescription in one field
					$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription . "<br/><br/>" . $jobs->job->originalXML->Jobs->JobPosition->JobTasksDescription;
					
					$first_characters = $reference;
					$last_characters = $jobs->job->originalXML->Jobs->JobPosition['id'];
					$companyID = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->CompanyID;
					$jobs->job->title  = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription;
					
					include 'functions/generalEnrichmentXML.php';
							
					// Save the modified jobs XML
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