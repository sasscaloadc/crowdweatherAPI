<h2> PUT - Location </h2>
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
$data["id"] = 24;
$data["latitude"] = -44.313;
$data["longitude"] = 35.8123;
$data["userid"] = 4;
$data["name"] = "secondbase";


//$data = Array();
//$data["latitude"] = -11.101;
//$data["longitude"] = 14.25599;
//$data["name"] = "barndoor";

$fields = json_encode($data);

//$xmlOut = new SimpleXMLElement("<User/>");
//arrayToXML($data, $xmlOut); // $display_array populated by implementations of get_array_all or get_array_instance
//$fields = $xmlOut->asXML();

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://afrihost.sasscal.org/api/locations");
//curl_setopt($ch, CURLOPT_USERPWD, "erik@sas.co.na:qwe123");
//curl_setopt($ch, CURLOPT_USERPWD, "root@newestplace.org:xcv123d");
curl_setopt($ch, CURLOPT_USERPWD, "martin@weather.co.za:qwe123");
//curl_setopt($ch, CURLOPT_USERPWD, "guest:guest");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/json", "Content-Length: " . strlen($fields)));
//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/xml", "Content-Length: " . strlen($fields)));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

if ( curl_exec($ch)) {;
	echo "<br>Done: Success";
} else {
	echo "<br>Done: Failure<br>";
    echo "cURL error : ".curl_error($ch);
}
	
?>
<br/>
End
<br/>

