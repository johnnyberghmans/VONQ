<?php
$xmlstr = file_get_contents('php://input');
$jobs = new SimpleXMLElement($xmlstr);
$id = (string)$jobs->job['id'];
$first_characters = explode("-", $id)[0];//substr($id, 0, 6);

if($first_characters == "DUORS" || $first_characters == "DUOTT" || $first_characters == "CXRSP" || $first_characters == "AFASRS" || $first_characters == "AFASTT") {
$output = '
	{
	  "success": {
		"'.$id.'": {  
		  "data": "https://beheer.ingoedebanen.nl/bekijk?id=3f2d79258d1e1",
		  "sso": "https://beheer.ingoedebanen.nl/vacatures?id=3f2d79258d1e1",
		  "dist": "https://beheer.ingoedebanen.nl/vacatures/distribueren?id=3f2d79258d1e1",
		  "origin":"VONQ Spoofer"
		}
	  }
	}';
}
else{
	$output = '{"failed": {"all": "vonqproxy: id must start with DUORS-"}}';
}

echo $output;

?>

