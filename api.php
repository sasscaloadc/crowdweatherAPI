<?php
include_once 'User.class.php';

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}
//echo "_REQUEST : " . var_dump($_REQUEST)."<br/><br/>";  // requests starts with everything AFTER 10.0.0.10/api/
//excho "_SERVER[REQUEST_URI] : " . var_dump($_SERVER['REQUEST_URI'])."<br/><br/>";
try {
	
	$user = array_key_exists('PHP_AUTH_USER',$_SERVER) ? $_SERVER['PHP_AUTH_USER'] : "";
	$pass = array_key_exists('PHP_AUTH_PW',$_SERVER) ? $_SERVER['PHP_AUTH_PW'] : "";

	$message = "";
	$validated = apiDB::validate($user,$pass,$message);

	if ($validated < -1) {
  		header('WWW-Authenticate: Basic realm="SASSCAL Weather"');
  		header('HTTP/1.0 401 Unauthorized');
		die($message.$validated);
	} else {
		if ($validated < 0) {
  			echo $message;
		} else {
			error_log("AUTHORIZED AS -".$user."- -".$pass."-");

			$args = explode('/', rtrim($_REQUEST['request'], '/'));
	
	
			$firstclass = strtolower(array_shift($args));  //pop first object off the URL
	
			$extension = "html";
			//ignore the extension if there is one.
			if ((sizeof($args) == 0) && (strrpos($firstclass, ".") !== false)) {
				$extension = substr($firstclass,strrpos($firstclass, ".")+1);
				$firstclass = substr($firstclass,0,strrpos($firstclass, "."));
			}
			$firstclass = ($firstclass[strlen($firstclass) - 1] == 's') ? ucfirst(substr($firstclass, 0, strlen($firstclass) - 1)) : ucfirst($firstclass);
			$reflector = new ReflectionClass($firstclass);
			$object = $reflector->newInstance();
		
			$object->setupClass($args, $validated, $extension);
    			echo $object->process();
		}
	}
	
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}

?>
