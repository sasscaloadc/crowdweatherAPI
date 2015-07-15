<?php

	
	function arrayToXML(Array $array, SimpleXMLElement &$xml) {
		foreach($array as $key => $value) {
			// Array
			if (is_array($value)) {
				$xmlChild = (is_numeric($key)) ? $xml->addChild("id_$key") : $xml->addChild($key);
				arrayToXML($value, $xmlChild);
				continue;
			}   
			
			// Object
			if (is_object($value)) {
				$xmlChild = $xml->addChild(get_class($value));
				arrayToXML(get_object_vars($value), $xmlChild);
				continue;
			}
			
			// Simple Data Element
			(is_numeric($key)) ? $xml->addChild("id_$key", $value) : $xml->addChild($key, $value);
		}
	}   
	
$data = Array();
$data["id"] = "4";
$data["email"] = "sasscal@enron.com";
$data["password"] = "dontspill";
//$data["locations"] = Array();

//$location = Array();
//$location["latitude"] = -11.123;
//$location["longitude"] = 14.2546;
//$location["name"] = "platteklip";
//array_push($data["locations"], $location);

//$location = Array();
//$location["latitude"] = -11.101;
//$location["longitude"] = 14.25599;
//$location["name"] = "barndoor";
//array_push($data["locations"], $location);

$fields = json_encode($data);

$xmlOut = new SimpleXMLElement("<User/>");
arrayToXML($data, $xmlOut); // $display_array populated by implementations of get_array_all or get_array_instance
$fields = $xmlOut->asXML();

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "http://10.0.0.10/api/users");

curl_setopt($ch, CURLOPT_POST, true);
//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/json", "Content-Length: " . strlen($fields)));
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/xml", "Content-Length: " . strlen($fields)));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

curl_exec($ch);

	
?>

