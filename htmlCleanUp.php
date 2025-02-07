

<?php

$testString = '<description><![CDATA[<p><span style="background-color: transparent; font-family: Arial, sans-serif; font-size: 10pt; white-space-collapse: preserve;">Wij bij RS Services zijn gepassioneerd door het leveren van uitstekende service aan onze klanten en streven naar een positieve werkomgeving voor al onze medewerkers. Als groeiend bedrijf zijn we momenteel op zoek naar een dynamische parttime onthaalbediende/receptionist(e) om ons team te versterken.</span></p><p><span style="background-color: transparent; font-size: 10pt; font-family: Arial, sans-serif; font-variant-numeric: normal; font-variant-east-asian: normal; font-variant-alternates: normal; font-variant-position: normal; font-variant-emoji: normal; vertical-align: baseline; white-space-collapse: preserve;">Onze klant, gevestigd te Aartselaar, is een van de grote spelers gespecialiseerd in het ontwerpern van zakelijke mobiliteits</span><span style="background-color: transparent; font-size: 10pt; font-family: Arial, sans-serif; font-variant-numeric: normal; font-variant-east-asian: normal; font-variant-alternates: normal; font-variant-position: normal; font-variant-emoji: normal; vertical-align: baseline; white-space-collapse: preserve;">oplossingen.</span></p><p><span style="background-color: transparent; font-size: 10pt; font-family: Arial, sans-serif; font-variant-numeric: normal; font-variant-east-asian: normal; font-variant-alternates: normal; font-variant-position: normal; font-variant-emoji: normal; vertical-align: baseline; white-space-collapse: preserve;">Onze klant plant een verhuizing naar Bornem eind 2026- begin 2027.</span></p><p><span style="font-size: 10pt; font-family: Arial, sans-serif; background-color: transparent; font-style: normal; font-variant-numeric: normal; font-variant-east-asian: normal; font-variant-alternates: normal; font-variant-position: normal; font-variant-emoji: normal; vertical-align: baseline; white-space-collapse: preserve;"><span id="docs-internal-guid-e2de4aa3-7fff-fa81-c82d-275e041e8bd0"></span></span></p>]]></description><profileRequirement><![CDATA[<p></p><p dir="ltr" style="line-height:1.38;margin-top:0pt;margin-bottom:0pt;"><span style="font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffffff;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Wie zoeken we ?&#x0026;nbsp;</span></p><ul style="margin-top:0;margin-bottom:0;padding-inline-start:48px;"><li dir="ltr" style="list-style-type:disc;font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;" aria-level="1"><p dir="ltr" style="line-height:1.38;margin-top:8pt;margin-bottom:0pt;" role="presentation"><span style="font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffffff;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Zit mensen met de glimlach verder helpen in je bloed?</span></p></li><li dir="ltr" style="list-style-type:disc;font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;" aria-level="1"><p dir="ltr" style="line-height:1.38;margin-top:0pt;margin-bottom:0pt;" role="presentation"><span style="font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffffff;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Heb je een verzorgd voorkomen?</span></p></li><li dir="ltr" style="list-style-type:disc;font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;" aria-level="1"><p dir="ltr" style="line-height:1.38;margin-top:0pt;margin-bottom:8pt;" role="presentation"><span style="font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffffff;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Kan je vlot overweg met de computer en kan je je vlot uitdrukken in het Nederlands, Frans en Engels?&#x0026;nbsp;</span></p></li></ul><p><span id="docs-internal-guid-ba5da05d-7fff-1323-3cd9-86f2bcd2ed2d"></span></p><p dir="ltr" style="line-height:1.38;margin-top:8pt;margin-bottom:8pt;"><span style="font-size:10pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffffff;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">Dan ben jij de medewerker die wij zoeken!</span></p>]]></profileRequirement>';

echo "<textArea cols=420 rows=12>";
echo $testString;
echo "</textArea>";

echo "<hr/>";
echo "strip_tags";
echo "<br/>";


echo "<textArea cols=420 rows=12>";
echo html_entity_decode( strip_tags($testString, '<br><br/><ul></ul><li></li>'));

echo "</textArea>";

echo "<hr/>";
echo "preg_replace";
echo "<br/>";


echo "<textArea cols=420 rows=12>";
$testString = strip_tags($testString, '<ul><li><br><b><i>');
//echo preg_replace("/<([a-z][a-z0-9]*)[^<|>]*?(\/?)>/si",'<$1$2>', $testString);
echo preg_replace( "#(<[a-zA-Z0-9]+)[^\>]+>#", "\\1>", $testString );
echo "</textArea>";
?>
