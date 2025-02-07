<html>
<head>
</head>
<body>
<H1>VacPolice Demo</H1>
<H4>This Demo will
<br/>- redirect to Minggo when vacnumber
<br/>-
<br/>-
<br/>- redirect BACK to the website on all other case inc. Exceptions...
</H4>


<?php
/*
VACPOLICE DEMO
Call with: http://127.0.0.1/vacpolice/vacpolice.php
An APPLY-URL from teh website would look like : http://127.0.0.1/vacpolice/vacpolice.php/DUORS-973121
*/
echo 'Current PHP version: ' . phpversion();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
use JsonException;
 const JSON_THROW_ON_ERROR = 1;

$array_customsectors = array(
array('nl' => 'HR','fr' => 'HR','en' => 'HR','id' => 1877,),
array('nl' => 'ICT','fr' => 'ICT','en' => 'ICT','id' => 1878,),
array('nl' => 'administratie','fr' => 'administration','en' => 'administration','id' => 1879,),
array('nl' => 'bouw','fr' => 'construction','en' => 'construction','id' => 1880,),
array('nl' => 'creatief','fr' => 'créatif','en' => 'creative','id' => 1881,),
array('nl' => 'customer service','fr' => 'customer service','en' => 'customer service','id' => 1882,),
array('nl' => 'financieel','fr' => 'finances','en' => 'finance','id' => 1883,),
array('nl' => 'horeca','fr' => 'horeca','en' => 'horeca','id' => 1884,),
array('nl' => 'juridisch','fr' => 'juridiques','en' => 'legal','id' => 1885,),
array('nl' => 'logistiek','fr' => 'logistique','en' => 'logistics','id' => 1886,),
array('nl' => 'marketing','fr' => 'marketing','en' => 'marketing','id' => 1887,),
array('nl' => 'medisch','fr' => 'médical','en' => 'medical','id' => 1888,),
array('nl' => 'onderwijs','fr' => 'enseignement','en' => 'education','id' => 1889,),
array('nl' => 'productie','fr' => 'production','en' => 'production','id' => 1890,),
array('nl' => 'sport & ontspanning','fr' => 'sports & loisirs','en' => 'sports & leisure','id' => 1891,),
array('nl' => 'techniek','fr' => 'technique','en' => 'technics','id' => 1892,),
array('nl' => 'transport','fr' => 'transport','en' => 'transport','id' => 1893,),
array('nl' => 'veiligheid','fr' => 'sécurité','en' => 'security','id' => 1894,),
array('nl' => 'verkoop','fr' => 'vente','en' => 'sales','id' => 1895,),
);

$uriSegments = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (sizeof($uriSegments) > 3)
{
  echo "<p>The VacancyId received:"  .  $uriSegments[3] . "</p>";

}
else
{
  echo "<p>ERROR NOT ENOUGH ELEMENTS IN URI ARRAY</p>";
}
$vacoriginlabel = "DUORS";
if (substr($uriSegments[3], 0, strlen($vacoriginlabel)) === $vacoriginlabel)
{
	echo "<p>A RANDSTAD DUO VACANCY</p>";
}


$vacoriginlabel = "CXRSS";
if (substr($uriSegments[3], 0, strlen($vacoriginlabel)) === $vacoriginlabel)

{
		 echo "<p>A RSS CARERIX VACANCY</p>";
}

$vacoriginlabel = "CXRSP";
if (substr($uriSegments[3], 0, strlen($vacoriginlabel)) === $vacoriginlabel)

{
		 echo "<p>A PROFESSIONAL CARERIX VACANCY</p>";
}


$vacoriginlabel = "CXAU";
if (substr($uriSegments[3], 0, strlen($vacoriginlabel)) === $vacoriginlabel)

{
		 echo "<p>A AUSY CARERIX VACANCY</p>";
}


$vacoriginlabel = "DUOTT";
if (substr($uriSegments[3], 0, strlen($vacoriginlabel)) === $vacoriginlabel)

{
		 echo "<p>A TEMPOTEAM DUO VACANCY</p>";
}



$cURLConnection = curl_init();
curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($cURLConnection, CURLOPT_URL, 'https://www.randstad.be/api/search/jobs?ids='.$uriSegments[3]);
curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, 1);
$phoneList = curl_exec($cURLConnection);
curl_close($cURLConnection);
//echo "zzzz" . $phoneList;

//$jsonArrayResponse = json_decode($phoneList,true);
//print_r($jsonArrayResponse);

try {
 $jsonArrayResponse =  json_decode($phoneList, true, 512, JSON_THROW_ON_ERROR);
}
catch (\JsonException $exception) {
  echo $exception->getMessage(); // displays "Syntax error"
}



//echo $jsonArrayResponse["name"];

//echo $jsonArrayResponse['array'][0]['JobInformation'];
echo "<br/>jobId: ". $jsonArrayResponse['items'][0]['jobId'];
echo "<br/>Title: ". $jsonArrayResponse['items'][0]['JobInformation']['Title'];
echo "<br/>SectorId: ". $jsonArrayResponse['items'][0]['JobInformation']['SectorId'];
$vac_sectorid = 0;
$vac_sectorid = $jsonArrayResponse['items'][0]['JobInformation']['SectorId'];

echo "<br/>JobTypeId: ". $jsonArrayResponse['items'][0]['JobInformation']['JobTypeId'];
echo "<br/>Sector: ". $jsonArrayResponse['items'][0]['JobInformation']['Sector'];
echo "<br/>CustomSector: ". $jsonArrayResponse['items'][0]['JobInformation']['CustomSector'];
echo "<br/>CustomSectorId: ". $jsonArrayResponse['items'][0]['JobInformation']['CustomSectorId'];
$vac_customsectorid = 0;
$vac_customsectorid = $jsonArrayResponse['items'][0]['JobInformation']['CustomSectorId'];

$sendToMinggo = False;


if ($vac_customsectorid == 1886) {
 // code to be executed if this condition is true;
 echo ("<H3>SECTOR LOGISTIEK</H3>");
    $sendToMinggo = True;

} elseif ($vac_customsectorid == 1880) {
	echo ("<H3>SECTOR BOUW</H3>");
  //code to be executed if first condition is false and this condition is true;
  $sendToMinggo = True;
} else {
  //code to be executed if all conditions are false;
}

$converted_res = $sendToMinggo ? 'surely' : 'surely NOT ';


$targeturl_duors_nl = "https://www.randstad.be/nl/werknemers/jobs/apply/DUORS-973872";
$targeturl_duors_fr = "https://www.randstad.be/fr/candidats/jobs/apply/DUORS-957701";
$targeturl_duors_en = "https://www.randstad.be/en/candidates/jobs/apply/DUORS-973872";

$targeturl_duott_nl = "https://www.randstad.be/nl/werknemers/jobs/apply/DUORS-973872";
$targeturl_duott_fr = "https://www.randstad.be/fr/candidats/jobs/apply/DUORS-957701";
$targeturl_duott_en = "https://www.randstad.be/en/candidates/jobs/apply/DUORS-973872";


$targeturl_cxrss_nl = "https://www.randstad.be/nl/werknemers/jobs/apply/DUORS-973872";
$targeturl_cxrss_fr = "https://www.randstad.be/fr/candidats/jobs/apply/DUORS-957701";
$targeturl_cxrss_en = "https://www.randstad.be/en/candidates/jobs/apply/DUORS-973872";


$targeturl_cxrsp_nl = "https://www.randstad.be/nl/werknemers/jobs/apply/DUORS-973872";
$targeturl_cxrsp_fr = "https://www.randstad.be/fr/candidats/jobs/apply/DUORS-957701";
$targeturl_cxrsp_en = "https://www.randstad.be/en/candidates/jobs/apply/DUORS-973872";


$targeturl_cxau_nl = "https://www.randstad.be/nl/werknemers/jobs/apply/DUORS-973872";
$targeturl_cxau_fr = "https://www.randstad.be/fr/candidats/jobs/apply/DUORS-957701";
$targeturl_cxau_en = "https://www.randstad.be/en/candidates/jobs/apply/DUORS-973872";

echo "<H2>Policeman will " . $converted_res  .  " redirect this Application to Minggo</H2>";

$script = "<script>window.location = 'http://www.example.com/newlocation';</script>";

$script = "<script type='text/javascript'>window.setTimeout(function() {window.location.href='http://www.example.com/target';}, 5000);</script>";

//echo $script;
?>
</body>
</html>

<!--
EXAMPLE OF A VACANCY JSON


{
  "success": true,
  "status": 200,
  "statusText": "OK",
  "message": "OK",
  "items": [
    {
      "jobId": "DUORS-973121",
      "JobInformation": {
        "Title": "Private Label Assistant",
        "Role": "administratief medewerker",
        "Description": "- Het aanspreekpunt zijn voor de export landen. Je plaatst voor hen de bestellingen en volgt ze op, je beantwoord de marketing aanvragen, plaatst de producten in hun databases, volgt de facturatie op, enz.<br>- Het ondersteunen van de Private Label afdeling in dagelijkse administratieve taken zoals voorbereiden van Power Point presentaties, voorbereiden van dossiers voor vergaderingen, opstellen van notulen van vergaderingen, klassement, maar ook seedings en mailings.<br>- Het onderhouden van de communicatie met de winkels en opvolgen van hun aanvragen.<br>Zorgen voor de administratie met betrekking tot de private label producten (creatie van producten in het bestand, opvolgen van Q&A documentatie, beheer/opvolging van cosmetisch dossier en registratie van de producten in Europese bestanden, registratie producten in de AS Watson bestanden, cosmetovigilance).<br>-Het up-to-date houden van verschillende databestanden in Excel en zorgen voor de standaardrapportages van de afdeling Private Label.<br>-Het herwerken, updaten, vertalen en nalezen van Private Label documenten (Presentaties, Packaging, Perscommunicatie, Advertenties, Trainingsboeken,¿).<br>- Het aanspreekpunt zijn voor andere diensten en voor de winkels wat betreft Private Label materiaal.<br>",
        "Skills": null,
        "SkillsList": null,
        "Qualification": "- Je bent in het bezit van een Bachelor diploma.<br>- Je hebt 1 tot 3 jaar ervaring in een administratieve functie.<br>- Je hebt een vlotte kennis Excel, Word, Powerpoint en Outlook.<br>- Je spreekt en schrijft vlot Frans, Nederlands en Engels.<br>- Je kan vlot samenwerken.<br>- Je kan goed organiseren.<br>- Je kan goed plannen en prioriteiten stellen.<br>- Je bent stressbestendig, communicatief, analytisch.<br>- Je kan initiatief nemen en anticiperen<br>",
        "QualificationsList": null,
        "SectorId": "32",
        "SectorIdSpecified": "true",
        "JobTypeId": "2",
        "JobTypeIdSpecified": "true",
        "Hours": "38 uren per week",
        "Duration": "",
        "Experience": "<1 jaar",
        "ExperienceList": null,
        "Industry": "Groot- en detailhandel",
        "Residency": "",
        "Quantity": "0",
        "QuantitySpecified": "false",
        "Internal": null,
        "Url": "https://www.randstad.be/job/DUORS-973121/",
        "DetailsUrl": "https://www.randstad.be/job/DUORS-973121/",
        "JobInternal": "false",
        "JobInternalSpecified": "true",
        "CustomSectorId": "1879",
        "CustomSectorIdSpecified": "true",
        "SubSector": null,
        "BrandId": "0",
        "BrandIdSpecified": "false",
        "Education": null,
        "EducationsList": null,
        "Responsibilities": null,
        "ResponsibilitiesList": null,
        "ExtraInformation": null,
        "JobType": "Tijdelijk",
        "Sector": "Retail & Groothandel",
        "CustomSector": "administratie"
      },
      "Salary": {
        "CurrencyId": "3",
        "CurrencyIdSpecified": "true",
        "Rate": "",
        "Benefits": "Je voelt je thuis in een wereld van luxe cosmetica- en schoonheidsproducten<br>In een informele werkomgeving waar verantwoordelijkheid, teamwork, ondernemerschap en een goede sfeer belangrijke uitgangspunten zijn.<br>",
        "BenefitsList": null,
        "SalaryMin": "0",
        "SalaryMinSpecified": "false",
        "SalaryMax": "0",
        "SalaryMaxSpecified": "false",
        "CompensationType": "per uur",
        "CompensationTypeSpecified": "true",
        "HideSalaryMin": "false",
        "HideSalaryMinSpecified": "true",
        "HideSalaryMax": "false",
        "HideSalaryMaxSpecified": "true",
        "CompensationTypeId": "1"
      },
      "JobLocation": {
        "DerivedCity": "Vilvoorde",
        "CountryId": "21",
        "CountryIdSpecified": "true",
        "LanguageId": "3",
        "LanguageIdSpecified": "true",
        "City": "Vilvoorde",
        "Neighborhood": null,
        "Postcode": "1800",
        "RegionId": "0",
        "RegionIdSpecified": "false",
        "RegionCode": "12",
        "Street": "Schaarbeeklei, 499",
        "RemoteWork": "false",
        "Longitude": "4.4337",
        "Latitude": "50.929722",
        "Region": "Vlaams-Brabant",
        "RegionAutocomplete": "Vlaams-Brabant",
        "Country": "België",
        "Language": "Nederlands",
        "CityAutocomplete": "Vilvoorde",
        "PostcodeAutocomplete": "1800",
        "CityRegionAutocomplete": "Vilvoorde, Vlaams-Brabant",
        "LocationPin": {
          "lat": 50.929722,
          "lon": 4.4337
        }
      },
      "JobDates": {
        "DateCreatedTime": "2023-04-18 09:48:54",
        "DateCreated": "2023-04-18",
        "DateCreatedSpecified": "true",
        "DateExpire": "2023-05-15",
        "DateExpireSpecified": "true",
        "DateStart": "0001-01-01",
        "DateStartSpecified": "false",
        "DateEnd": "0001-01-01",
        "DateEndSpecified": "false",
        "DateModified": "2023-04-18",
        "DateModifiedSpecified": "true",
        "DateModifiedTime": "2023-04-18 09:50:09"
      },
      "JobData": {
        "JobId": "DUORS-973121",
        "JobDisplayId": "DUORS-1472395",
        "JobInformation": {
          "Title": "Private Label Assistant",
          "Role": "administratief medewerker",
          "Description": "- Het aanspreekpunt zijn voor de export landen. Je plaatst voor hen de bestellingen en volgt ze op, je beantwoord de marketing aanvragen, plaatst de producten in hun databases, volgt de facturatie op, enz.<br>- Het ondersteunen van de Private Label afdeling in dagelijkse administratieve taken zoals voorbereiden van Power Point presentaties, voorbereiden van dossiers voor vergaderingen, opstellen van notulen van vergaderingen, klassement, maar ook seedings en mailings.<br>- Het onderhouden van de communicatie met de winkels en opvolgen van hun aanvragen.<br>Zorgen voor de administratie met betrekking tot de private label producten (creatie van producten in het bestand, opvolgen van Q&A documentatie, beheer/opvolging van cosmetisch dossier en registratie van de producten in Europese bestanden, registratie producten in de AS Watson bestanden, cosmetovigilance).<br>-Het up-to-date houden van verschillende databestanden in Excel en zorgen voor de standaardrapportages van de afdeling Private Label.<br>-Het herwerken, updaten, vertalen en nalezen van Private Label documenten (Presentaties, Packaging, Perscommunicatie, Advertenties, Trainingsboeken,¿).<br>- Het aanspreekpunt zijn voor andere diensten en voor de winkels wat betreft Private Label materiaal.<br>",
          "Skills": null,
          "SkillsList": null,
          "Qualification": "- Je bent in het bezit van een Bachelor diploma.<br>- Je hebt 1 tot 3 jaar ervaring in een administratieve functie.<br>- Je hebt een vlotte kennis Excel, Word, Powerpoint en Outlook.<br>- Je spreekt en schrijft vlot Frans, Nederlands en Engels.<br>- Je kan vlot samenwerken.<br>- Je kan goed organiseren.<br>- Je kan goed plannen en prioriteiten stellen.<br>- Je bent stressbestendig, communicatief, analytisch.<br>- Je kan initiatief nemen en anticiperen<br>",
          "QualificationsList": null,
          "SectorId": "32",
          "SectorIdSpecified": "true",
          "JobTypeId": "2",
          "JobTypeIdSpecified": "true",
          "Hours": "38 uren per week",
          "Duration": "",
          "Experience": "<1 jaar",
          "ExperienceList": null,
          "Industry": "Groot- en detailhandel",
          "Residency": "",
          "Quantity": "0",
          "QuantitySpecified": "false",
          "Internal": null,
          "Url": "https://www.randstad.be/job/DUORS-973121/",
          "DetailsUrl": "https://www.randstad.be/job/DUORS-973121/",
          "JobInternal": "false",
          "JobInternalSpecified": "true",
          "CustomSectorId": "1879",
          "CustomSectorIdSpecified": "true",
          "SubSector": null,
          "BrandId": "0",
          "BrandIdSpecified": "false",
          "Education": null,
          "EducationsList": null,
          "Responsibilities": null,
          "ResponsibilitiesList": null,
          "ExtraInformation": null,
          "JobType": "Tijdelijk",
          "Sector": "Retail & Groothandel",
          "CustomSector": "administratie"
        },
        "Salary": {
          "CurrencyId": "3",
          "CurrencyIdSpecified": "true",
          "Rate": "",
          "Benefits": "Je voelt je thuis in een wereld van luxe cosmetica- en schoonheidsproducten<br>In een informele werkomgeving waar verantwoordelijkheid, teamwork, ondernemerschap en een goede sfeer belangrijke uitgangspunten zijn.<br>",
          "BenefitsList": null,
          "SalaryMin": "0",
          "SalaryMinSpecified": "false",
          "SalaryMax": "0",
          "SalaryMaxSpecified": "false",
          "CompensationType": "per uur",
          "CompensationTypeSpecified": "true",
          "HideSalaryMin": "false",
          "HideSalaryMinSpecified": "true",
          "HideSalaryMax": "false",
          "HideSalaryMaxSpecified": "true",
          "CompensationTypeId": "1"
        },
        "JobLocation": {
          "DerivedCity": "Vilvoorde",
          "CountryId": "21",
          "CountryIdSpecified": "true",
          "LanguageId": "3",
          "LanguageIdSpecified": "true",
          "City": "Vilvoorde",
          "Neighborhood": null,
          "Postcode": "1800",
          "RegionId": "0",
          "RegionIdSpecified": "false",
          "RegionCode": "12",
          "Street": "Schaarbeeklei, 499",
          "RemoteWork": "false",
          "Longitude": "4.4337",
          "Latitude": "50.929722",
          "Region": "Vlaams-Brabant",
          "RegionAutocomplete": "Vlaams-Brabant",
          "Country": "België",
          "Language": "Nederlands",
          "CityAutocomplete": "Vilvoorde",
          "PostcodeAutocomplete": "1800",
          "CityRegionAutocomplete": "Vilvoorde, Vlaams-Brabant",
          "LocationPin": {
            "lat": 50.929722,
            "lon": 4.4337
          }
        },
        "Contact": {
          "Name": "Marilyn Collette",
          "Code": "831",
          "ConsultantPhotoUrl": null,
          "ConsultantBiography": "",
          "Email": "zaventem_831@randstad.be",
          "ConsultantEmail": "zaventem_831@randstad.be",
          "ConsultantJobTitle": null,
          "Phone": "02 254 86 97",
          "Fax": "",
          "Office": "",
          "OfficeId": "831",
          "Street": "Brussels National Airport Bus",
          "City": "Zaventem",
          "Postcode": "1930",
          "CountryId": "21",
          "CountryIdSpecified": "true"
        },
        "JobDates": {
          "DateCreatedTime": "2023-04-18 09:48:54",
          "DateCreated": "2023-04-18",
          "DateCreatedSpecified": "true",
          "DateExpire": "2023-05-15",
          "DateExpireSpecified": "true",
          "DateStart": "0001-01-01",
          "DateStartSpecified": "false",
          "DateEnd": "0001-01-01",
          "DateEndSpecified": "false",
          "DateModified": "2023-04-18",
          "DateModifiedSpecified": "true",
          "DateModifiedTime": "2023-04-18 09:50:09"
        },
        "CustomFields": {
          "CustomField1": "",
          "CustomField2": "1361906",
          "CustomField3": ""
        },
        "Tags": null,
        "JobIdentity": {
          "DovaJobId": "41458421",
          "CompanyId": "240",
          "AccountId": "1247",
          "CompanyName": "Randstad Belgium"
        },
        "Portals": null,
        "ClientInformation": {
          "ClientLogoUrl": "",
          "ClientName": "ETS. A. PASQUASY",
          "ClientWebsiteUrl": "",
          "ClientId": "0459076848",
          "ClientPhotoUrl": null,
          "ClientVideoUrl": null,
          "ClientBiography": "Wij bieden je een uitdagende fulltime functie in een krachtige en dynamische internationale onderneming. ICI PARIS XL maakt deel uit van de A.S. Watson Group, ¿s werelds grootste retailer op het gebied van health & beauty. Ambitieuze professionals die kansen benutten, beslissingen durven te nemen en zich thuis voelen in een informele, no-nonsense en resultaatgerichte organisatie, kunnen rekenen op uitstekende toekomstperspectieven. Prima arbeidsvoorwaarden spreken voor zich.<br>"
        },
        "LocationData": {
          "MonsterLocationId": "21087224",
          "CountryISOCode": "BE",
          "CountryNameEn": "Belgium",
          "CountryNameLang1": "Belgique",
          "CountryNameLang2": "België",
          "CountryNameLang3": null,
          "Region1NameEn": "Flemish Region",
          "Region1NameLang1": "Région Flamande",
          "Region1NameLang2": "Vlaams Gewest",
          "Region1NameLang3": null,
          "Region2NameEn": "Flemish Brabant",
          "Region2NameLang1": "Brabant Flamand",
          "Region2NameLang2": "Vlaams-Brabant",
          "Region2NameLang3": null,
          "Region3NameEn": "Halle-Vilvoorde",
          "Region3NameLang1": "Hal-Vilvorde",
          "Region3NameLang2": "Halle-Vilvoorde",
          "Region3NameLang3": null,
          "Region4NameEn": "Vilvorde",
          "Region4NameLang1": "Vilvorde",
          "Region4NameLang2": "Vilvoorde",
          "Region4NameLang3": null,
          "CityNameEn": "Vilvoorde",
          "CityNameLang1": "Vilvorde",
          "CityNameLang2": "Vilvoorde",
          "CityNameLang3": null,
          "Postcode": "1800",
          "Latitude": "50.937158",
          "Longitude": "4.455095"
        },
        "BlueXSanitized": {
          "ReferenceNumber": "DUORS-973121",
          "Title": "private-label-assistant",
          "TitlePlain": "private label assistant",
          "Role": "administratief-medewerker",
          "JobType": "tijdelijk",
          "Specialism": "administratie",
          "Region": "vlaams-brabant",
          "CompanyName": "randstad-belgium",
          "CompensationType": "per-uur",
          "CustomSector": "administratie",
          "Country": "belgie",
          "Language": "nederlands",
          "City": "vilvoorde",
          "ClientName": "ets-a-pasquasy",
          "CustomField2": "1361906"
        },
        "BlueXJobData": {
          "JobId": "DUORS-973121",
          "ReferenceNumber": "DUORS-973121",
          "Title": "Private Label Assistant",
          "Role": "administratief medewerker",
          "JobType": "Tijdelijk",
          "JobTypeId": "2",
          "Specialism": "administratie",
          "SpecialismId": "1879",
          "SubSpecialism": null,
          "JobCategory": null,
          "JobCategoryId": null,
          "Region": "Vlaams-Brabant",
          "RegionId": "12",
          "City": "Vilvoorde",
          "SectorOfEmployment": null,
          "JobDetailsUrl": "https://www.randstad.be/nl/werknemers/jobs/private-label-assistant_vilvoorde_DUORS-973121/",
          "Description": "- Het aanspreekpunt zijn voor de export landen. Je plaatst voor hen de bestellingen en volgt ze op, je beantwoord de marketing aanvragen, plaatst de producten in hun databases, volgt de facturatie op, enz.<br>- Het ondersteunen van de Private Label afdeling in dagelijkse administratieve taken zoals voorbereiden van Power Point presentaties, voorbereiden van dossiers voor vergaderingen, opstellen van notulen van vergaderingen, klassement, maar ook seedings en mailings.<br>- Het onderhouden van de communicatie met de winkels en opvolgen van hun aanvragen.<br>Zorgen voor de administratie met betrekking tot de private label producten (creatie van producten in het bestand, opvolgen van Q&A documentatie, beheer/opvolging van cosmetisch dossier en registratie van de producten in Europese bestanden, registratie producten in de AS Watson bestanden, cosmetovigilance).<br>-Het up-to-date houden van verschillende databestanden in Excel en zorgen voor de standaardrapportages van de afdeling Private Label.<br>-Het herwerken, updaten, vertalen en nalezen van Private Label documenten (Presentaties, Packaging, Perscommunicatie, Advertenties, Trainingsboeken,¿).<br>- Het aanspreekpunt zijn voor andere diensten en voor de winkels wat betreft Private Label materiaal.<br>",
          "CompanyNameId": "240",
          "CompanyName": "Randstad Belgium",
          "CompensationType": "per uur",
          "ClientName": "ETS. A. PASQUASY",
          "CustomField2": "1361906"
        }
      }
    }
  ]
}

-->