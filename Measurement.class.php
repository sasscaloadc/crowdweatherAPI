<?php
require_once("RESTObject.class.php");
require_once("Rain.class.php");             
require_once("Mintemp.class.php");             

abstract class Measurement extends RESTObject
{
	public $reading = '';
	public $fromdate = '';
	public $todate = '';
	public $locationid = '';
	public $userid = '';
	public $note = '';

	abstract function columnName();
	abstract function tableName();
	
	public function get_array_instance() {
		$array = Array();
		$array["id"] = $this->id;
		$array["locationid"] = $this->locationid;
		$array[$this->columnName()] = $this->reading;
		$array["fromdate"] = $this->fromdate;
		$array["todate"] = $this->todate;
		$array["note"] = $this->note;
		return $array;
	}
	
	public function get_array_all() {
		if (empty($this->userid)) {
			$user = apiDB::getUserByEmail( $_SERVER['PHP_AUTH_USER'] );
			$locations = apiDB::getUserLocations($user->id, $this->columnName());
			if (count($locations) == 1) {
				return apiDB::getLocationMeasurements($locations[0]->locationid, $user->userid, get_class($this));  
			} else {
				$error = Array();
				$error["ERROR"] = "cannot display all measurements - please specify a location ID";
				return $error;  //This is a hack
			}
		}
		return apiDB::getLocationMeasurements($this->locationid, $this->userid, get_class($this));  
	}

	public function put_array($array) {
		$reflector = new ReflectionClass(get_class($this));
		$measurement = $reflector->newInstance();

		if (!empty($array["id"])) {
			$measurement = apiDB::getMeasurement($array["id"], get_class($this));
		} else {
			if (empty($array["locationid"])) {
				//Throwing this error before calling getUserByLocationId() below.
				return "Error: Location ID has to be specified if measurement ID is not set";	
			}
		}

		$locid = empty($array["locationid"]) ? $measurement->locationid : $array["locationid"];
                $user = apiDB::getUserByLocationId($locid);
                if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
                        return "Not authorized to add measurements to location ".$locid;
                }
		
		$measurement->reading = empty($array[$measurement->columnName()]) ? $measurement->reading : $array[$measurement->columnName()];
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
                $reflector = new ReflectionClass(get_class($this)); 
                $measurement = $reflector->newInstance();

                $user = apiDB::getUserByLocationId($array["locationid"]);
                if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
                        return "Not authorized to add measurements to location ".$array["locationid"];
                }

		$measurement->reading = $array[$this->columnName()];
		$measurement->fromdate = $array["fromdate"];
		$measurement->todate = $array["todate"];
		$measurement->locationid = $array["locationid"];
		$measurement->note = $array["note"];
error_log("READING: ".$this->columnName()." = ". $measurement->reading);		
		return apiDB::addMeasurement($measurement);
	}
	
	public function delete_array($array) {
		if (!empty($array["id"])) {
			$measurement = apiDB::getMeasurement($array["id"], get_class($this));

			if (empty($measurement->id)) {
				return "Error: \"".$measurement->columnName()."\" measurement with id ".$array["id"]." not found";
			}
			$user = apiDB::getUserByLocationId($measurement->locationid);
	                if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
        	                return "Error: Not authorized to delete ".$measurement->columnName()." measurement from location ".$measurement->locationid;
                	}

			return apiDB::deleteMeasurement($array["id"], $this->tableName());   
		} 
		return "ERROR: No measurement ID specified for deletion";
	}
	
	public function getInstanceDetails($id) {
		$measurement = apiDB::getMeasurement($id, get_class($this));   
		
		if (empty($measurement->id)) {
			return self::NO_SUCH_ID;
		}
		$user = apiDB::getUserByLocationId($measurement->locationid);
		if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
			return self::ACCESS_DENIED;
		}
		$this->id = $measurement->id;
		$this->userid = $measurement->userid;
		$this->locationid = $measurement->locationid;
		$this->reading = $measurement->reading;
		$this->fromdate = $measurement->fromdate;
		$this->todate = $measurement->todate;
		$this->note = $measurement->note;
		// 	Preserving $this->access however, to retain admin rights.
		return self::SETUP_OK;
	}
	
	function apiLink() {
		$useridString = empty($this->userid) ? "" : "/users/".$this->userid;
		$locationidString = empty($this->locationid) ? "" : "/locations/".$this->locationid;
		$linkString = "https://".apiDB::$servername."/".apiDB::dirname().$useridString.$locationidString."/".$this->columnName()."/" . $this->id ;
		return "<a href=\"".$linkString."\">".$this->fromdate."->".$this->todate."</a>";
	}
	
}

?>
