<?php

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
function searchInArray($lookupvalue, $array, $lookupEntity) {
   foreach ($array as $key => $val) { 
 		if($lookupEntity=="client"){
		   if(isset($val['clientID'])){
			   if ($val['clientID'] == $lookupvalue) {
				 $resultSet['isActive'] = $val['isActive'];
				 $resultSet['company'] = $val['company'];
				 $resultSet['environment'] = $val['environment'];
				 return $resultSet;
				}
		   }	 
	   }	 
	   if($lookupEntity=="kbo"){
		   if(isset($val['unit'])){
			   if ($val['unit'] == $lookupvalue) {
				 $resultSet['kboCompany'] = $val['kboCompany'];
				 $resultSet['kboOffice'] = $val['kboOffice'];
				 $resultSet['vdabID'] = $val['vdabID'];
				 return $resultSet;
			   }
		   }	 
	   }	 
	   if($lookupEntity=="rascoDUO"){
		   if(isset($val['newDUOID'])){
			   if ($val['newDUOID'] == $lookupvalue) {
				 $resultSet['rascoID'] = $val['rascoID'];
				 $resultSet['iscoID'] = $val['iscoID'];
				 return $resultSet;
			   }
		   }	 
	   }   
	   
	   if($lookupEntity=="isco"){
		   if(isset($val['afasID'])){
			 //  echo $val['afasID']."++++". $lookupvalue."-";
			   if ((string)$val['afasID'] == (string)$lookupvalue) {
			//	   echo "fouddddddd";
				 $resultSet['afasID'] = $val['afasID'];
				 $resultSet['iscoID'] = $val['iscoID'];
				 $resultSet['vdabCompetences'] = $val['vdabCompetences'];
				 return $resultSet;
			   }
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

function addCDataWithBreaks($elementName, $elementContent){
	//check if element already has CDATA element. If not add the CDATA, if yes do no manipulations

		// Verwijder de buitenste tags en behoud de inhoud
		preg_match("/<{$elementName}>(.*?)<\/{$elementName}>/s", $elementContent, $matches);

		// Controleer of de inhoud is gevonden
		if (isset($matches[1])) {
			$innerContent = $matches[1];
		//	echo $innerContent;
			if (strpos($elementContent, "<![CDATA[") === false) {
				//echo 'accCDATA';
				// Zet de inhoud in een CDATA-sectie
				$xmlWithCDATA = "<![CDATA[{$innerContent}]]>";
				return $xmlWithCDATA;
			} 
			else {
				//echo 'DONT add accCDATA';
				// Zet de inhoud in een CDATA-sectie
				$xmlWithCDATA = "$innerContent}";
				return $xmlWithCDATA;
			} 
	} 
	
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
	//echo $xml;
	$url = $endPoint;
	//echo $url . "***";
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
		//echo "***" . $error . "***";
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