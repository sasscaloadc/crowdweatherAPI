<?php
//include("Layout.html");
abstract class RESTObject
{
	/**
     * Error code constants
     */
	const SETUP_OK = 0;
	const ID_ALREADY_SETUP = 1;
	const NO_SUCH_ID = 2;
	const ACCESS_DENIED = 3;
	
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();
    /**
     * Property: extension
     * The ultimate display format
     */
     protected $extension = Null;

    /**
     * Property: file
     * Stores the input of the PUT request
     */
     protected $file = Null;
	 
	 public $id = Null;  // every RESTObject should have an ID field
	 
	 protected $access = 0;
	/**
	  * This is the function that must return an HTML link to this object in this REST API
	  */
	abstract function apiLink();

	/** 
	  * This function should return an array of all properties that will be displayed for a specific instance of this class.
	  * The array can be nested (array of arrays) and will be displayed as XML or as JSON or HTML, depending on the extension used in the URL
	  */
	abstract function get_array_instance();

	/** 
	  * This function should return an array of all Objects of this class in the database.
	  * The array can be nested (array of arrays) and will be displayed as XML or as JSON or HTML, depending on the extension used in the URL
	  */
	abstract function get_array_all();

	abstract function put_array($array);
	abstract function post_array($array, &$message);
	abstract function delete_array($array);
	/** 
	  * This function is used to retrieve specific details of an object from the database and populating this object's properties.
	  */
	abstract function getInstanceDetails($id);

    public function setupClass($args, $access, $ext = 'html') {  // this used to be a constructor, but changed to a method

        $this->args = $args;
		$this->access = $access;
		
		//Get extension either from endpoint if array size is 1, or from args if array size > 1. Either way the last item in the list.
		$this->extension = substr(strrchr(end($this->args), "."),1);
		
		//Remove extension from last item in the array, if there was one
		if (!empty($this->extension)) {
			$this->args[sizeof($this->args)-1] = substr(end($this->args),0,strrpos(end($this->args), "."));
		} else {
			$this->extension = $ext;
		}
		
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method) {
        case 'DELETE':
            $this->file = file_get_contents("php://input");
			break;
        case 'POST':
            $this->file = file_get_contents("php://input");
            break;
        case 'GET':
            $this->request = $this->_cleanInputs($_GET);
            break;
        case 'PUT':
            $this->file = file_get_contents("php://input");
            break;
        default:
            $this->_response('Invalid Method', 405);
            break;
        }
    }

	public function setupInstance($id) {
		if (empty($this->id)) {
			return $this->getInstanceDetails($id);
		} else {
			return self::ID_ALREADY_SETUP;
		}
	}
	
    public function process() {
		if (sizeof($this->args) == 0) {
			return $this->execute();
		} else {  //there are 1 or more arguments
			if (is_numeric($this->args[0])) {
				$id = array_shift($this->args);
				switch ($this->setupInstance($id)) {
					case self::SETUP_OK:
						return $this->process(); // continue to process remaining arguments
						break;
					case self::ID_ALREADY_SETUP:
						return $this->_response("Object cannot be instantiated more than once: ".get_class($this)."->id = $this->id", 404);	
						break;
					case self::NO_SUCH_ID:
						return $this->_response("Object ID not found: ".get_class($this)."->id : $id", 404);	
						break;
					case self::ACCESS_DENIED:
						return $this->_response("Not Authorized to access object: ".get_class($this)."->id : $id", 404);	
						break;
				}
			} else {
				if (method_exists($this, $this->args[0])) {
					$rest_method = array_shift($this->args);
					return $this->_response($this->{$rest_method}($this->args)); // run method on remainind arguments
				} else {
					return $this->_response("No Such Method in ".get_class($this).": $this->args[0]", 404);	
				}
			}
		}
    }

    public function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return $data;
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));   // clean up tags to avoid injection
        }
        return $clean_input;
    }

    private function _requestStatus($code) {
		$status = array(
					100 => 'Continue',  
					101 => 'Switching Protocols',  
					200 => 'OK',
					201 => 'Created',  
					202 => 'Accepted',  
					203 => 'Non-Authoritative Information',  
					204 => 'No Content',  
					205 => 'Reset Content',  
					206 => 'Partial Content',  
					300 => 'Multiple Choices',  
					301 => 'Moved Permanently',  
					302 => 'Found',  
					303 => 'See Other',  
					304 => 'Not Modified',  
					305 => 'Use Proxy',  
					306 => '(Unused)',  
					307 => 'Temporary Redirect',  
					400 => 'Bad Request',  
					401 => 'Unauthorized',  
					402 => 'Payment Required',  
					403 => 'Forbidden',  
					404 => 'Not Found',  
					405 => 'Method Not Allowed',  
					406 => 'Not Acceptable',  
					407 => 'Proxy Authentication Required',  
					408 => 'Request Timeout',  
					409 => 'Conflict',  
					410 => 'Gone',  
					411 => 'Length Required',  
					412 => 'Precondition Failed',  
					413 => 'Request Entity Too Large',  
					414 => 'Request-URI Too Long',  
					415 => 'Unsupported Media Type',  
					416 => 'Requested Range Not Satisfiable',  
					417 => 'Expectation Failed',  
					418 => 'Incorrect Date Order',  
					420 => 'Date overlap in Measurement table',  
					500 => 'Internal Server Error',  
					501 => 'Not Implemented',  
					502 => 'Bad Gateway',  
					503 => 'Service Unavailable',  
					504 => 'Gateway Timeout',  
					505 => 'HTTP Version Not Supported');
        return ($status[$code])?$status[$code]:$status[500]; 
    }

	/**
	  *  This function gets called on the last Object in the REST URL. It will execute the relevant method (GET/PUT/POST/DELETE) and display or update results.
	  */
	public function execute() {
		$message = "";
		switch ($this->method) {
			case "GET":
				return $this->display(empty($this->id) ? $this->get_array_all() : $this->get_array_instance());
				break;
			case "PUT":
				return $this->put_array($this->prepareContent($this->file));
				break;
			case "POST":
				$code = $this->post_array($this->prepareContent($this->file), $message); 
				return $this->_response($message, $code);
				break;
			case "DELETE":
				return $this->delete_array($this->prepareContent($this->file));
				break;
		}
	}
	
	public function prepareContent($data) {
		$object_array = Array();
		$content = substr(strrchr($_SERVER["HTTP_ACCEPT"], "/"),1);
		
		switch ($content) {
			case "json": $object_array = json_decode($data,TRUE);
						break;
			case "xml": $xml = simplexml_load_string($data);
						$json = json_encode($xml);
						$object_array = json_decode($json,TRUE);
						break;
			default: json_decode($data);
					 if (json_last_error() == JSON_ERROR_NONE) {
						$object_array = json_decode($data,TRUE);
					 } else {
						$xml = simplexml_load_string($data);
						$json = json_encode($xml);
						$object_array = json_decode($json,TRUE);
					 }
		}
		return $object_array;
	}
	
	public function display($display_array) {  
        	header("Access-Control-Allow-Origin: *");
        	header("Access-Control-Allow-Methods: *");

		switch($this->extension) {
			case "xml":
				header("Content-Type: application/xml");
				$xmlOut = new SimpleXMLElement("<".get_class($this)."/>");
				$this->arrayToXML($display_array, $xmlOut); // $display_array populated by implementations of get_array_all or get_array_instance
				return $xmlOut->asXML();
				break;
			case "json":
				header("Content-Type: application/json");
				return json_encode($this->objectsToArrays($display_array));
				break;
			case "txt":
				header("Content-Type: text/html");
				error_log("calling TXT");
				return "Text not implemented yet";
				break;
			default: //HTML
				header("Content-Type: text/html");
				$out = '<h2 align="center">' .get_class($this).'s</h2>';
				$out .= $this->toHTML($display_array);
		return $out;
		}
	
	}

	/**
	  *  This function returns an object as an array of arrays - all properties that are objects, converted to arrays.
	  *  It is a pre-processing step before converting to json or XML.
	  */
	private function objectsToArrays(Array $objectArray) {
		$array = Array();
		foreach($objectArray as $key => $value) {
			// Object
			if (is_object($value)) {
				$key = get_class($value)."_".$key;  
				$value = is_a($value, "RESTObject") ? $value->get_array_instance() : get_object_vars($value);
			}
			
			if (is_array($value)) {
				$array[$key] = $this->objectsToArrays($value);
			} else {
				// Simple Data Element
				$array[$key] = $value;
			}
		}
		return $array;
	}   
	
	private function arrayToXML(Array $array, SimpleXMLElement &$xml) {
		foreach($array as $key => $value) {
			// Array
			if (is_array($value)) {
				$xmlChild = (is_numeric($key)) ? $xml->addChild("id_$key") : $xml->addChild($key);
				$this->arrayToXML($value, $xmlChild);
				continue;
			}   
			
			// Object
			if (is_object($value)) {
				$xmlChild = $xml->addChild(get_class($value));
				$vars_array = is_a($value, "RESTObject") ? $value->get_array_instance() : get_object_vars($value);
				$this->arrayToXML($vars_array, $xmlChild);
				continue;
			}
			
			// Simple Data Element
			(is_numeric($key)) ? $xml->addChild("id_$key", $value) : $xml->addChild($key, $value);
		}
	}   
	
	private function toHTML($displayArray) { 
		
		$out = "<ul>";
		foreach($displayArray as $key => $value) {	
			if (is_array($value)) {  
				if (($key == "rain") || ($key == "mintemp")) {
					$out .= "<li><a href=\"https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."/".$key."\">".$key."</a></li>";
				} else {
					$out .= "<li>$key: </li><ul>";
					$out .= $this->toHTML($value);
					$out .= "</ul>";
				}
			} else {
					$out .= "<li>";
					if (is_a($value, "RESTObject")) {
							$out .= $value->apiLink();
					} else {
							$out .= $key.": ".$value;
					}
					$out .= "</li>";
			}
		}
		$out .= "</ul>";
	
//		$out .= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>';
//		$out .= '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>';
		return $out;
	}
}
?>
