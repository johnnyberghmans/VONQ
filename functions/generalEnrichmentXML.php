<?php

	// Verwijder alleen het xmlns attribuut uit de JobPosition tag (nu ook als het achteraan staat)
	$xmlString = $jobs->saveXML(); // Verkrijg de XML als string

	// Verwijder alleen de xmlns uit de JobPosition tag (als het er is)
	$xmlWithoutNamespace = preg_replace('/(<JobPosition[^>]*)( xmlns="[^"]*")([^>]*>)/', '$1$3', $xmlString);
	$jobs = new SimpleXMLElement($xmlWithoutNamespace);
	

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

			//Only adjust the enddate for French publications to the maximum enddate allowed by FOREM
			if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobLanguage == 'FR'){
				$endDate = adjustEndDate($startDate, $endDate);
			}
						
			
			$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['startdate'] = $startDate;
			$jobs->job->originalXML->Jobs->JobPosition->Channels->Channel['enddate'] = $endDate;				
		}

		//For RS and TT we need to do some logic on the jobtitle to pass the iscoID, for RSP this logic is not needed. The jobTitle can be passed as is
		if($company == "RSP") {
			$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle= ltrim($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle, '0 ');
			//the applyURL from CX needs to be send as applyURL
			$url = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->applyURL; 
			unset($jobs->job->originalXML->Jobs->JobPosition->JobDetails->applyURL);
			
			
		}
		else {
			//Indien de url van Sphere http:// of https:// bevat dan halen we dat uit de url en sturen de url door naar vonq zonder prefix
			$url = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->PostedUrl; 
			
			//for AFAS we need to do other logic for passing the isco code
			if ($reference == "AFASRS" || $reference == "AFASTT" ){
		
				$myRoot = $_SERVER["DOCUMENT_ROOT"];
				// echo $myRoot
				include($myRoot . '/r/vonq/config/afas_functions_mapping.inc');				
				
				//include "../config/afas_functions_mapping.inc";
			//	$functieInfo = searchInArray($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription, $functiesAfas, "isco");
			//		$iscoID = $functieInfo['iscoID'];
					//$jobtitle = $functieInfo['jobtitle'];	
					
					//if no isco code is found we take the default 2423 = hr consultant, recruiter
			//		if($iscoID==""){
			//			$iscoID="2423";
			//		}
					
			//		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle = "isco_".$iscoID;
			}	
			else{
				//The new jobTitles will be passed with a number higher than 10020 So for all jobTitles <10020 we can pass the jobTitle as it is without conversion
				if (intVal($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle)<10020){
					$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle= ltrim($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle, '0 ');
				}
				//Search for function in mapping array
				else {
					//do magic search for jobtitle in $functies, if not found return error				
					$functieInfo = searchInArray($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle, $functies,"rascoDUO");
					$iscoID = $functieInfo['iscoID'];
					$rascoID = $functieInfo['rascoID'];				
			
					if ($iscoID != ""){
						$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle = "isco_".$iscoID;
					}
					else {
						$error = 'No function found in the rasco functions mapping table for JobTitle : '.$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle;
						$subjectTag = ' - #mappingError - Function not found in the rasco functions#';
					}		
					
				}
			}
		}
		
		//if($url==""){
		//	$url = $prefixPostedURL . $newRef;
		//	}
		//$clean_url = str_replace(array("http://", "https://"), "", $url);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->PostedUrl = addCData($url);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitleDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobBranch);
		
		
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobCompanyProfile= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobCompanyProfile);
		//echo $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription;

		//$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription->asXML());
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		

		//echo "****************".$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobDescription;
		//$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobRequirements->JobRequirementsDescription);
		//$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobOffer->JobOfferDescription);
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
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobProfileDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobProfileDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobContentDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobContentDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		
		$tag = $jobs->job->originalXML->Jobs->JobPosition->JobTasksDescription;
		$jobs->job->originalXML->Jobs->JobPosition->JobTasksDescription = addCDataWithBreaks($tag->getName(),$tag->asXML());
		
		$jobs->job->originalXML->Jobs->JobPosition->JobExperienceDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobExperienceDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobLanguagesDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobLanguagesDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobEducationDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobEducationDescription);
		$jobs->job->originalXML->Jobs->JobPosition->JobContentDescription= addCData($jobs->job->originalXML->Jobs->JobPosition->JobContentDescription);
		
		//TT dienstencheques for company 0467127056 we replace contractype to z
		if($companyID=='0467127056'){
			$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobContractType = 'Z';//addCData('Z');
			$debugString .= "companyID=='0467127056' we assign JobContractType to 'Z' \n\n";
		}
		
		
		if ($reference == "AFASRS" || $reference == "AFASTT" ){
			//remove the original VDABArea from AFAS because it is empty, add a new one
			unset($jobs->job->originalXML->Jobs->JobPosition->VDABArea);
			
			//we need to split AFASRS-3502 to get the 3502 to do the lookup
			$fieID = $jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle;
			
			//get vdab competences from AFAS
			$functieInfo = searchInArray($fieID, $functiesAfas, "isco");
			//$functieInfo = searchInArray("3502", $functiesAfas, "isco");
			
			if (is_array($functieInfo)) {
				
				$afasID = $functieInfo['afasID'];
				$iscoID = $functieInfo['iscoID'];				
				
				//we send isco code to VONQ for AFAS publications
				$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobTitle = "isco_".$iscoID;
				
				$vdabCompetences = $functieInfo['vdabCompetences'];

				$newChild = simplexml_load_string($vdabCompetences);
					
				// Import the new XML into the existing XML structure
				$node = dom_import_simplexml($jobs->job->originalXML->Jobs->JobPosition);
				$newNode = $node->ownerDocument->importNode(dom_import_simplexml($newChild), true);
				$node->appendChild($newNode);	
				// Output the modified XML
				//echo $Jobs->asXML();


				//	$target = $jobs->job->originalXML->Jobs->JobPosition;
				//	simplexml_insert_after($vdabCompetences, $target);
				}
				
				
				
		}			
				
		if(!isset($jobs->job->originalXML->Jobs->JobPosition->VDABArea)){
			$jobs->job->originalXML->Jobs->JobPosition->addChild('VDABArea');
		}

		$vdabArea = $jobs->job->originalXML->Jobs->JobPosition->VDABArea ?? null;
		
		

		if ($vdabArea) {
					
			//print_r($jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]'));
			//check for tag SERV competence, if it doesnot exists add it
			
			if (!$vdabArea->xpath('//Competency')){
				$debugString .= 'Compence tag SERV competence not found so we will add it. \n\n';
					
				$jobs->job->originalXML->Jobs->JobPosition->VDABArea->addChild('Competency')->addAttribute('name', 'SERV competence');
				$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]')[0]->addAttribute('required', 'true');
				$jobs->job->originalXML->Jobs->JobPosition->VDABArea->xpath('//Competency[@name="SERV competence"]')[0]->addChild('CompetencyId')->addAttribute('id', '16856');		
				}
		
		
			//check for tag Study Code, if it doesnot exists add it
			if (!$vdabArea->xpath('//Competency[@name="Study Code"]')){
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
		}
		//VONQ expects the tag JobWorkingHour, if it is not send by the original xml from DUO we add it as an empty tag.
		if($jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobWorkingHours->JobWorkingHour == ''){
			$jobs->job->originalXML->Jobs->JobPosition->JobDetails->JobWorkingHours->JobWorkingHour = "D";
			$debugString .= "Tag JobWorkingHour is added to original xml\n\n";
		}
	
?>