<?php
require_once("RESTObject.class.php");
require_once 'Measurement.class.php';

class Location extends RESTObject
{
	public $latitude = '';
	public $longitude = '';
	public $name = '';
	public $userid = '';
	public $measurements = Array();

	function apiLink() {
		$useridString = empty($this->userid) ? "" : "/users/".$this->userid;
		$linkString = "https://".apiDB::$servername."/".apiDB::dirname().$useridString."/locations/" . $this->id ;
		return "<a href=\"".$linkString."\">".$this->name."</a>";
	}

	public function get_array_instance() {
		$array = Array();
		$array["latitude"] = $this->latitude;
		$array["longitude"] = $this->longitude;
		$array["name"] = $this->name;
		$array["userid"] = $this->userid;
		$array["id"] = $this->id;
		$array["measurements"] = $this->measurements;
		return $array;
	}

	public function get_array_all() {
		if (empty($this->userid)) {
			if ($this->access > 1) {
				return apiDB::getLocations();
			} else {
				$user = apiDB::getUserByEmail( $_SERVER['PHP_AUTH_USER'] );
				return apiDB::getUserLocations($user->id);
			}
		} else {
			return apiDB::getUserLocations($this->userid);
		}
	}

	public function put_array($array) {
		if ($this->access < 1) {
			return "Not authorized to make any updates : guest account";
        }

		$location = new Location();
		if (!empty($array["id"])) {
			$location = apiDB::getLocation($array["id"]);
		} // otherwise we'll just add a new location
		
		$location->name = empty($array["name"]) ? $location->name : $array["name"];
		$location->latitude = empty($array["latitude"]) ? $location->latitude : $array["latitude"];
		$location->longitude = empty($array["longitude"]) ? $location->longitude : $array["longitude"];
		$location->userid = empty($array["userid"]) ? $location->userid : $array["userid"];
			
		if (empty($location->id)) {
			return apiDB::addLocation($location);
		} else {
			return apiDB::updateLocation($location->id, $location);
		}
	}
	
	public function post_array($array) {
		$location = new Location();
		
		$location->name = $array["name"];
		$location->latitude = $array["latitude"];
		$location->longitude = $array["longitude"];
		$location->userid = $array["userid"];
		
		return apiDB::addLocation($location);
	}
	
	public function delete_array($array) {
		if (!empty($array["id"])) {
			return apiDB::deleteLocation($array["id"]);
		} 
		return "ERROR: No location ID specified for deletion";
	}	
	
	public function getInstanceDetails($id) {
		$location = empty($this->userid) ? apiDB::getLocation($id) : apiDB::getUserLocation($id, $this->userid, $this);

		if (empty($location->id)) {
			return self::NO_SUCH_ID;
		}
		$user = apiDB::getUser($location->userid);
error_log("AUTH:".$_SERVER['PHP_AUTH_USER']);
error_log("EMAIL:".$user->email);
error_log("ACCESS:".$this->access);
		if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
			return self::ACCESS_DENIED;
		}
		$this->latitude = $location->latitude;
		$this->longitude = $location->longitude;
		$this->name = $location->name;
		$this->userid = $location->userid;
		$this->id = $location->id;
		$this->measurements = $location->measurements;
		// 	Preserving $this->access however, to retain admin rights.
		return self::SETUP_OK;
	}
	
	/**
     * Measurements Endpoint
     */
     protected function measurements() {
		if (empty($this->id)) {
			return $this->_response("Location id not set. Cannot run \"measurements\" without a valid location id.", 404);	
		}
//		if (empty($this->userid)) {
//			return $this->_response("User id not set. Cannot run \"measurements\" without a valid user id on the location.", 404);	
//		}
		$msm = new Measurement();
		$msm->setupClass($this->args, $this->access, $this->extension);
		$msm->userid = $this->userid;
		$msm->locationid = $this->id;
		return $msm->process();
	 }

}

?>
