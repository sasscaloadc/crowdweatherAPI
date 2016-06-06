<h2>PUT - User</h2>
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
$data["id"] = "359";
//$data["email"] = "steve@biggerstuff.com";
//$data["password"] = "didntspilladrop";
$data["sub_gwadi"] = "0";


$fields = json_encode($data);

//$xmlOut = new SimpleXMLElement("<User/>");
//arrayToXML($data, $xmlOut); // $display_array populated by implementations of get_array_all or get_array_instance
//$fields = $xmlOut->asXML();

$ch = curl_init();

//curl_setopt($ch, CURLOPT_URL, "https://erik%40sas.co.na:qwe123@afrihost.sasscal.org/api/users");
curl_setopt($ch, CURLOPT_URL, "https://afrihost.sasscal.org/api/users");
curl_setopt($ch, CURLOPT_USERPWD, "erik@sas.co.na:qwe123");
//curl_setopt($ch, CURLOPT_USERPWD, "steve@biggerstuff.com:didntspill");
//curl_setopt($ch, CURLOPT_USERPWD, "guest:guest");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
//curl_setopt($ch, CURLOPT_CAPATH, "/home/syseng/certificates/"); 
//curl_setopt($ch, CURLOPT_CAINFO, "/home/syseng/certificates/afrihost.sasscal.org.cert_for_curl.pem"); 
//curl_setopt($ch, CURLOPT_CAINFO, "/home/syseng/certificates/thawte_CAroot.cert_for_curl.pem"); 
//curl_setopt($ch, CURLOPT_CAINFO, "/home/syseng/certificates/thawte_intermediate3.cert_for_curl.pem"); 
//curl_setopt($ch, CURLOPT_CAINFO, "thawte_CAroot.cert_for_curl.pem"); 
//curl_setopt($ch, CURLOPT_CAINFO, "afrihost.sasscal.org.cert_for_curl.pem"); 
//curl_setopt($ch, CURLOPT_CAINFO,  getcwd().'/home/syseng/certificates/thawte_intermediate3.crt');
//curl_setopt($ch, CURLOPT_CAINFO,  getcwd().'/home/syseng/certificates/thawte_intermediate3.cert_for_curl.pem');
//curl_setopt($ch, CURLOPT_SSLCERT, getcwd().'/home/syseng/certificates/afrihost.sasscal.org.cert_for_curl2.pem');
//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '5455c4l_'); 

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
