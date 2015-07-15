<?php
require_once("RESTObject.class.php");
require_once("apiDB.php");             

class Measurement extends RESTObject
{
	public $rain = '';
	public $mintemp = '';
	public $fromdate = '';
	public $todate = '';
	public $locationid = '';
	public $userid = '';
	public $note = '';
	
	public function get_array_instance() {
		$array = Array();
		$array["id"] = $this->id;
		$array["userid"] = $this->userid;
		$array["locationid"] = $this->locationid;
		$array["rain"] = $this->rain;
		$array["mintemp"] = $this->mintemp;
		$array["fromdate"] = $this->fromdate;
		$array["todate"] = $this->todate;
		$array["note"] = $this->note;
		return $array;
	}
	
	public function get_array_all() {
		if (empty($this->userid)) {
			$error = Array();
			$error["ERROR"] = "cannot display all measurements - please specifiy user ID and location ID";
			return $error;  //This is a hack
		}
		return apiDB::getLocationMeasurements($this->locationid, $this->userid);  
	}

	public function put_array($array) {
		$measurement = new Measurement();
//error_log("PUT!!");		
		if (!empty($array["id"])) {
			apiDB::getMeasurement($array["id"], $measurement);
		} // otherwise we'll just add a new measurement
		
		$measurement->rain = empty($array["rain"]) ? $measurement->rain : $array["rain"];
		$measurement->mintemp = empty($array["mintemp"]) ? $measurement->mintemp : $array["mintemp"];
		$measurement->fromdate = empty($array["fromdate"]) ? $measurement->fromdate : $array["fromdate"];
		$measurement->todate = empty($array["todate"]) ? $measurement->todate : $array["todate"];
		$measurement->locationid = empty($array["locationid"]) ? $measurement->locationid : $array["locationid"];
		$measurement->note = empty($array["note"]) ? $measurement->note : $array["note"];
			
		if (empty($measurement->id)) {
			return apiDB::addMeasurement($measurement);  
		} else {
			return apiDB::updateMeasurement($measurement->id, $measurement);  
		}
	}
	
	public function post_array($array) {
		$measurement = new Measurement();
		
		$measurement->rain = $array["rain"];
		$measurement->mintemp = $array["mintemp"];
		$measurement->fromdate = $array["fromdate"];
		$measurement->todate = $array["todate"];
		$measurement->locationid = $array["locationid"];
		$measurement->note = $array["note"];
		
		return apiDB::addMeasurement($measurement);
	}
	
	public function delete_array($array) {
		if (!empty($array["id"])) {
			return apiDB::deleteMeasurement($array["id"]);   
		} 
		return "ERROR: No measurement ID specified for deletion";
	}
	
	public function getInstanceDetails($id) {
		apiDB::getMeasurement($id, $this);   
	}
	
	function apiLink() {
		$useridString = empty($this->userid) ? "" : "/users/".$this->userid;
		$locationidString = empty($this->locationid) ? "" : "/locations/".$this->locationid;
		$linkString = "https://".apiDB::$servername."/".apiDB::dirname().$useridString.$locationidString."/measurements/" . $this->id ;
		return "<a href=\"".$linkString."\">".$this->fromdate."->".$this->todate."</a>";
	}
	
}

?>
