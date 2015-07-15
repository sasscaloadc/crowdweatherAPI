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
			return apiDB::getLocations();
		} else {
			return apiDB::getUserLocations($this->userid);
		}
	}

	public function put_array($array) {
		$location = new Location();
//error_log("PUT!!");		
		if (!empty($array["id"])) {
			apiDB::getLocation($array["id"], $location);
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
		if (empty($this->userid)) {
			apiDB::getLocation($id, $this);
		} else {
			apiDB::getUserLocation($id, $this->userid, $this);
		}
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
		$msm->setupClass($this->args, $this->extension);
		$msm->userid = $this->userid;
		$msm->locationid = $this->id;
		return $msm->process();
	 }

}

?>
