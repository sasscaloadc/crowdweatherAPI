<?php
include_once 'User.class.php';
include_once 'Location.class.php';
/*
 *  This class will maintain the connection pool to the database
 *  
 */
 
class apiDB { 
/*
 *  These details can be stored in a .conf file
 */
	static $servername = "afrihost.sasscal.org";
	static $DBservername = "localhost";
	static $username = "postgres";
	static $password = "5455c4l_";
	static $dbname = "crowdweather";

	static function validate($username, $password) {
		$conn = apiDB::getConnection();

		$sql = "SELECT password, access FROM cw_user WHERE email = '".$username."' ";
		$result = pg_query($conn, $sql);
		if (($result) && (!empty($password))) {
			$row = pg_fetch_array($result);
			if ($password === $row["password"]) {
				return $row["access"];
			}
		}
		pg_close($conn);
		return -1;
	}

	static function dirname() {
		return substr(strrchr(dirname(__FILE__), "/"), 1);
	}
	
	static function getConnection() {
		// Create connection
		$conn = pg_pconnect("host=".apiDB::$DBservername." dbname=".apiDB::$dbname." user=".apiDB::$username." password=".apiDB::$password);
		if (!$conn) {
			die("Database connection failed. ");
		}
		return $conn;
	}

	static function getUsers() {
		$conn = apiDB::getConnection();
		$users = Array();

		$sql = "SELECT * FROM cw_user";
		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			// output data of each row
			while($row = pg_fetch_array($result)) {
				$user = new User();
				$user->email = $row["email"];
				$user->password = $row["password"];
				$user->id = $row["id"];
				$user->locations = apiDB::getLocations($user->id); 
				array_push($users, $user);
			}
		}
		pg_close($conn);
		return $users;
	}

	static function getUserByLocationId($locationid) {
		$conxn = apiDB::getConnection();
		
		$user = new User();
		$sql = "SELECT userid FROM userlocation WHERE locationid = ".$locationid;
		$result = pg_query($conxn, $sql);
		
		if ($result && (pg_num_rows($result) > 0)) {
			$row = pg_fetch_array($result);
			$user = apiDB::getUser($row["id"]);
		}
		pg_close($conxn);
		return $user;
	}

	static function getUserByEmail($email) {
		$conxn = apiDB::getConnection();
		
		$user = new User();
		$sql = "SELECT id FROM cw_user WHERE email = '".$email."' ";
		$result = pg_query($conxn, $sql);
		
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			$user = apiDB::getUser($row["id"]);
		}
		pg_close($conxn);
		return $user;
	}

	static function getUser($userid) {
		$conxn = apiDB::getConnection();

		$user = new User();
		$sql = "SELECT * FROM cw_user WHERE id = ".$userid;
		$result = pg_query($conxn, $sql);
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			$user->email = $row["email"];
			$user->password = $row["password"];
			$user->id = $row["id"];
			$user->locations = apiDB::getUserLocations($userid); 
		} 
		pg_close($conxn);
		return $user;
	}

	static function getUserLocations($userid) {
		if (empty($userid)) {
			return "ERROR: GetUserLocations called without valid userid ";
		}
		$conn = apiDB::getConnection();
		$locations = Array();
		$sql = "SELECT l.* FROM cw_user u INNER JOIN userlocation ul on ul.userid = u.id INNER JOIN location l on l.id = ul.locationid WHERE u.id = ".$userid;
		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			while($row = pg_fetch_array($result)) {
				$loc = new Location();  // should any args be parsed here?
				$loc->latitude = $row["latitude"];
				$loc->longitude = $row["longitude"];
				$loc->name = $row["name"];
				$loc->id = $row["id"];
				$loc->userid = $userid;
				$loc->measurements = apiDB::getLocationMeasurements($loc->id, $userid);
				array_push($locations, $loc);
			}
		}
		pg_close($conn);
		return $locations;
	}

	static function getLocations() {
		$conn = apiDB::getConnection();
		$locations = Array();

		$sql = "SELECT l.*, ul.userid FROM location l INNER JOIN userlocation ul on l.id = ul.locationid ";
		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			while($row = pg_fetch_array($result)) {
				$loc = new Location();  
				$loc->latitude = $row["latitude"];
				$loc->longitude = $row["longitude"];
				$loc->name = $row["name"];
				$loc->id = $row["id"];
				$loc->userid = $row["userid"];
				$loc->measurements = apiDB::getLocationMeasurements($loc->id, $loc->userid);
				array_push($locations, $loc);
			}
		}
		pg_close($conn);
		return $locations;
	}

	static function getLocation($locationid) {
		$conxn = apiDB::getConnection();

		$location = new Location();
		$sql = "SELECT l.*, ul.userid FROM location l INNER JOIN userlocation ul on l.id = ul.locationid WHERE id = ".$locationid;
		$result = pg_query($conxn, $sql);
		
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			$location->latitude = $row["latitude"];
			$location->longitude = $row["longitude"];
			$location->name = $row["name"];
			$location->id = $row["id"];
			$location->userid = $row["userid"];
			$location->measurements = apiDB::getLocationMeasurements($locationid, $row["userid"]);
		} 
		pg_close($conxn);
		return $location;
	}
	
	static function getUserLocation($locationid, $userid) {
		$conxn = apiDB::getConnection();

		$location = new Location();
		$sql = "SELECT * FROM location l INNER JOIN userlocation ul on l.id = ul.locationid WHERE id = ".$locationid." AND userid = ".$userid;
		$result = pg_query($conxn, $sql);
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			$location->latitude = $row["latitude"];
			$location->longitude = $row["longitude"];
			$location->name = $row["name"];
			$location->id = $row["id"];
			$location->userid = $row["userid"];
			$location->measurements = apiDB::getLocationMeasurements($locationid, $userid);
		} 
		pg_close($conxn);
		return $location;
	}

	static function getMeasurement($measurementid, &$measurement) {
		$conxn = apiDB::getConnection();

		$sql = "SELECT * FROM measurement WHERE id = ".$measurementid;
		$result = pg_query($conxn, $sql);
		if ($result) {
			$row = pg_fetch_array($result);
			$measurement->id = $row["id"];
			$measurement->rain = $row["rain"];
			$measurement->mintemp = $row["mintemp"];
			$measurement->fromdate = $row["fromdate"];
			$measurement->todate = $row["todate"];
			$measurement->locationid = $row["locationid"];
			$measurement->note = $row["note"];
		} 
		pg_close($conxn);
	}
	
	static function getLocationMeasurements($locationid, $userid) {
		if (empty($locationid)) {
			return "ERROR: GetLocationMeasurements called without valid location id ";
		}
		if (empty($userid)) {
			return "ERROR: GetLocationMeasurements called without valid user id ";
		}
		$conn = apiDB::getConnection();
		$measurements = Array();
		$sql = "SELECT m.* FROM measurement m WHERE m.locationid = ".$locationid;
		$result = pg_query($conn, $sql);
		if ($result) {
			while($row = pg_fetch_array($result)) {
				$msm = new Measurement();  
				$msm->id = $row["id"];
				$msm->rain = $row["rain"];
				$msm->mintemp = $row["mintemp"];
				$msm->fromdate = $row["fromdate"];
				$msm->todate = $row["todate"];
				$msm->locationid = $row["locationid"];
				$msm->userid = $userid;
				$msm->note = $row["note"];
				array_push($measurements, $msm);
			}
		}
		pg_close($conn);
		return $measurements;
	}
	
	static function addUser(&$user) {
		if (get_class($user) != "User") {
				return "Error, received object other than User";
		}
		if (empty($user->email)) {
			return "Error, no email specified for user";
		}
		if (empty($user->password)) {
			return "Error, no password specified for user";
		}
		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO cw_user (email, password) values ('".$user->email."', '".$user->password."') RETURNING id ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return $rows." User(s) Added";
		} else {
			return "Error with insert query : ".pg_last_error($conxn);
		}
	}
	
	static function updateUser($userid, $user) {
		if (get_class($user) != "User") {
				return "Error, received object other than User";
		}
		$dbUser = apiDB::getUser($userid);
		
		if (empty($dbUser->id)) {
			return "Error, Invalid User ID for Update";
		}

		$updatestring = "set ";
		$updatestring .= "email = ".(empty($user->email) ? "email" : "'".$user->email."'");
		$updatestring .= ", ";
		$updatestring .= "password = ".(empty($user->password) ? "password" : "'".$user->password."'");

		$conxn = apiDB::getConnection();
		$sql = "UPDATE cw_user ".$updatestring." WHERE id = ".$userid;
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return $rows." User(s) updated";
		} else {
			return "Error with User update query : ".pg_last_error($conxn);
		}
	}
	
	static function deleteUser($userid) {
		if (empty($userid)) {
			return "Error, no user id specified for deleting user";
		}
		$conxn = apiDB::getConnection();
		$sql = "DELETE FROM userlocation WHERE userid = ".$userid." ; DELETE FROM cw_user WHERE id = ".$userid." RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "User not found: ".$userid : "User Deleted: ".$userid;
		} else {
			return "Error with delete query for user: ".pg_last_error($conxn);
		}
	}
	
	static function deleteUserByEmail($email) {
		if (empty($email)) {
			return "Error, no email specified for deleting user";
		}
		$conxn = apiDB::getConnection();
		$sql = "DELETE FROM userlocation WHERE userid = (SELECT id FROM cw_user WHERE email = '".$email."') ; DELETE FROM cw_user WHERE email = '".$email."' RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "User not found" : "User Deleted";
		} else {
			return "Error with delete query : ".pg_last_error($conxn);
		}
	}
	
	static function addLocation(&$location) {
		if (get_class($location) != "Location") {
				return "Error, received object other than Location";
		}
		if (empty($location->userid)) {
			return "Error, no userid specified for location";
		}
		if (empty($location->latitude)) {
			return "Error, no latitude specified for location";
		}
		if (empty($location->longitude)) {
			return "Error, no longitude specified for location";
		}
		if (empty($location->name)) {
			return "Error, no name specified for location";
		}
		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO location (latitude, longitude, name) values (".$location->latitude.",".$location->longitude.", '".$location->name."'); ";
		$sql .= "INSERT INTO userlocation (userid, locationid) values (".$location->userid.", (SELECT id FROM location WHERE latitude = ".$location->latitude."  and longitude = ".$location->longitude." and name = '".$location->name."')) RETURNING locationid ";
		
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return "Location Added";
		} else { 
			return "Error with insert query : ".pg_last_error($conxn);
		}
	}
	
	static function updateLocation($locationid, $location) {
		if (get_class($location) != "Location") {
				return "Error, received object other than Location for update";
		}
		$dbLocation = new Location();
		apiDB::getLocation($locationid, $dbLocation);
		
		if (empty($dbLocation->id)) {
			return "Error, Invalid Location ID for Update";
		}
		
		$conxn = apiDB::getConnection();
		$updatestring = "set ";
		$updatestring .= "latitude = ".(empty($location->latitude) ? "latitude" : "'".$location->latitude."'");
		$updatestring .= ", ";
		$updatestring .= "longitude = ".(empty($location->longitude) ? "longitude" : "'".$location->longitude."'");
		$updatestring .= ", ";
		$updatestring .= "name = ".(empty($location->name) ? "name" : "'".$location->name."'");

		$sql = "UPDATE location ".$updatestring." WHERE id = ".$locationid;
		
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return $rows." Location(s) updated";
		} else { 
			return "Error with Location update query : ".pg_last_error($conxn);
		}
	}

	static function deleteLocation($locationid) {
		if (empty($locationid)) {
			return "Error, no location id specified for deleting location";
		}
		$conxn = apiDB::getConnection();
		$sql = "DELETE FROM userlocation WHERE locationid = ".$locationid." ; DELETE FROM location WHERE id = ".$locationid." RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "Location not found: ".$locationid : "Location Deleted: ".$locationid;
		} else {
			return "Error with delete query for location: ".pg_last_error($conxn);
		}
	}
	
	static function addMeasurement(&$measurement) {
		if (get_class($measurement) != "Measurement") {
				return "Error, received object other than Measurement";
		}
		if (empty($measurement->rain) && empty($measurement->mintemp)) {
			return "Error, either rainfall or minimum temperature must be specified for measurement";
		}
		if (empty($measurement->locationid)) {
			return "Error, no location id specified for measurement";
		}
		if (empty($measurement->fromdate)) {
			return "Error, no 'from' date specified for measurement";
		}
		if (empty($measurement->todate)) {
			return "Error, no 'to' date specified for measurement";
		}
		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO measurement (fromdate, todate, locationid";
		$sql .= empty($measurement->rain)? "" : ", rain";
		$sql .= empty($measurement->mintemp)? "" : ", mintemp";
		$sql .= empty($measurement->note)? "" : ", note";
		$sql .= ") VALUES ('".$measurement->fromdate."','".$measurement->todate."', ".$measurement->locationid;
		$sql .= empty($measurement->rain)? "" : ", ".$measurement->rain;
		$sql .= empty($measurement->mintemp)? "" : ", ".$measurement->mintemp;
		$sql .= empty($measurement->note)? "" : ", '".$measurement->note."'";
		$sql .= "); ";
		
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return "Measurement Added";
		} else { 
			return "Error with measurement insert query : ".pg_last_error($conxn);
		}
	}
	
	static function updateMeasurement($measurementid, $measurement) {
		if (get_class($measurement) != "Measurement") {
				return "Error, received object other than Measurement";
		}
		$dbMeasurement = new Measurement();
		apiDB::getMeasurement($measurementid, $dbMeasurement);
		
		if (empty($dbMeasurement->id)) {
			return "Error, Invalid Location ID for Update";
		}
		
		$conxn = apiDB::getConnection();
		$updatestring = "set ";
		$updatestring .= "rain = ".(empty($measurement->rain) ? "rain" : $measurement->rain);
		$updatestring .= ", ";
		$updatestring .= "mintemp = ".(empty($measurement->mintemp) ? "mintemp" : $measurement->mintemp);
		$updatestring .= ", ";
		$updatestring .= "fromdate = ".(empty($measurement->fromdate) ? "fromdate" : "'".$measurement->fromdate."'");
		$updatestring .= ", ";
		$updatestring .= "todate = ".(empty($measurement->todate) ? "todate" : "'".$measurement->todate."'");
		$updatestring .= ", ";
		$updatestring .= "note = ".(empty($measurement->note) ? "note" : "'".$measurement->note."'");

		$sql = "UPDATE measurement ".$updatestring." WHERE id = ".$measurementid;
		
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return $rows." Measurement(s) updated";
		} else { 
			return "Error with Measurement update query : ".pg_last_error($conxn);
		}
	}

	static function deleteMeasurement($measurementid) {
		if (empty($measurementid)) {
			return "Error, no measurement id specified for deleting measurement";
		}
		$conxn = apiDB::getConnection();
		$sql = "DELETE FROM measurement WHERE id = ".$measurementid." RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "Measurement not found: ".$measurementid : "Measurement Deleted: ".$measurementid;
		} else {
			return "Error with delete query for Measurement: ".pg_last_error($conxn);
		}
	}

}
?>
