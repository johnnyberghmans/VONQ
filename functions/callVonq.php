<?php	

	//if callspoofer = 1 send to spoofer
	if($callSpoofer==1){
		$endPoint = $endPointSpoofer;
		$nameEndPoint = $nameEndPointSpoofer;
	}
	else{
		$endPoint = $endPointVONQ;
		$nameEndPoint = $nameEndPointVONQ;
	}

	$vonqResponse = vonqPoster(html_entity_decode($jobs->asXML()));
	//echo "***".$error  ."---";
	
	if ($error==''){;
		$debugString .= "\n\nResponse from ".$nameEndPoint."\n\n".$vonqResponse;
		$debugString .= "\n\nAugmented xml send to ".$nameEndPoint."\n\n".$jobs->asXML();
		$debugString .= "\n\nInput xml : \n\n".$xmlstr;
		
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
?>