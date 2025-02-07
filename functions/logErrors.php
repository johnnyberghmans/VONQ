<?php
//Carerix parameters to log 
$xmlPW = "ZAqizEho";
$cx_instance = "randstadberssm";
$error = "";
$vonqError = "";

$positiveResult = "17995";
$negativeResult = "17996";
$result = "";
$rsID = "0";

//function to create/update note in Carerix for logging
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

//function do logging to CX
function doLoggingToCx(){
	global $rsID, $nameEndPoint, $subjectTag, $debugString, $result, $doDebug;
	
	$cDataStart = "<![CDATA[";
	$cDataEnd = "]]>";
	if ($doDebug){
		$vXML = '<CRToDo>
			<name>VONQ LOG v2 : '.$rsID.' - '.$nameEndPoint.' - ' . $_SERVER['SERVER_NAME'] . $subjectTag .' </name>
			<subject>VONQ LOG v2 : '.$rsID.' - '.$nameEndPoint.' - ' . $_SERVER['SERVER_NAME'] . $subjectTag .' </subject>
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
}

?>