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

	static function validate($username, $password, &$message) {
		$conn = apiDB::getConnection();

		$sql = "SELECT password, access, verified, current_date - created::date as days, to_char(created, 'DD Mon YYYY') as crdate FROM cw_user WHERE email = '".$username."' ";
		$result = pg_query($conn, $sql);
		if (($result) && (!empty($password))) {
			$row = pg_fetch_array($result);
			if ($password === $row["password"]) {
				if (($row["verified"] > 0) || ($row["days"] < 5)) { 
					pg_close($conn);
					return $row["access"];
				} else {
					$message = "Not verified ".$username. " ".$row["crdate"];
					pg_close($conn);
					return -1;  // Not verified
				}
			}
		}
		$message = "Not authorized ";
		pg_close($conn);
		return -2;  // Not authorized
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

	static function getUsers($level = 1) {
		$conn = apiDB::getConnection();
		$users = Array();

		$sql = "SELECT * FROM cw_user";
		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			// output data of each row
			while($row = pg_fetch_array($result)) {
				if ($level > 0) {
					$user = new User();
					$user->email = $row["email"];
					$user->password = $row["password"];
					$user->id = $row["id"];
					$user->access = $row["access"];
					$user->verified = $row["verified"];
					$user->firstname = $row["firstname"];
					$user->lastname = $row["lastname"];
					$user->postal = $row["postal"];
					$user->phone = $row["phone"];
					$user->sub_summary = $row["sub_summary"];
					$user->sub_gwadi = $row["sub_gwadi"];
					$user->sub_stats = $row["sub_stats"];
					$user->locations = apiDB::getUserLocations($user->id, $level - 1); 
				} else {
					$user = $row["id"];
				}
				array_push($users, $user);
			}
		}
		pg_close($conn);
		return $users;
	}

	static function getUserByLocationId($locationid) {
		$conxn = apiDB::getConnection();
		
		$user = new User();
		$sql = "SELECT userid FROM location WHERE id = ".$locationid;
		//$sql = "SELECT userid FROM userlocation WHERE locationid = ".$locationid;
		$result = pg_query($conxn, $sql);
		
		if ($result && (pg_num_rows($result) > 0)) {
			$row = pg_fetch_array($result);
			$user = apiDB::getUser($row["userid"]);
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

	static function getUser($userid, $level = 1) {
		$conxn = apiDB::getConnection();

		$user = new User();
		$sql = "SELECT * FROM cw_user WHERE id = ".$userid;
		$result = pg_query($conxn, $sql);
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			if ($level > 0) {
				$user->email = $row["email"];
				$user->password = $row["password"];
				$user->id = $row["id"];
				$user->access = $row["access"];
				$user->verified = $row["verified"];
				$user->firstname = $row["firstname"];
				$user->lastname = $row["lastname"];
				$user->postal = $row["postal"];
				$user->phone = $row["phone"];
				$user->sub_summary = $row["sub_summary"];
				$user->sub_gwadi = $row["sub_gwadi"];
				$user->sub_stats = $row["sub_stats"];
				$user->locations = apiDB::getUserLocations($userid, $level - 1); 
			} else {
				$user = $row["id"];
			}
		} 
		pg_close($conxn);
		return $user;
	}

	static function getUserLocations($userid, $level = 1) {
		if (empty($userid)) {
			return "ERROR: GetUserLocations called without valid userid ";
		}
		$col = empty($_GET["measure"]) ? "rain" : $_GET["measure"];
		if (!in_array($col, array('rain', 'mintemp'))) {
			$col = "rain";
		}
		$conn = apiDB::getConnection();
		$locations = Array();
		$sql = "SELECT l.*, CASE WHEN MAX(m.created) IS NULL THEN '1900-01-01' ELSE MAX(m.created) END AS createdtime FROM cw_user u 
				INNER JOIN location l on l.userid = u.id 
				LEFT OUTER JOIN ".$col."measurement m on l.id = m.locationid
			WHERE u.id = ".$userid." 
			GROUP BY l.latitude, l.longitude, l.name, l.id
                        ORDER BY createdtime DESC ";

		//		INNER JOIN userlocation ul on ul.userid = u.id 
		//		INNER JOIN location l on l.id = ul.locationid 

		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			while($row = pg_fetch_array($result)) {
				if ($level > 0) {
					$loc = new Location();  // should any args be parsed here?
					$loc->latitude = $row["latitude"];
					$loc->longitude = $row["longitude"];
					$loc->name = $row["name"];
					$loc->id = $row["id"];
					$loc->userid = $userid;
					$loc->rain = apiDB::getLocationMeasurements($loc->id, $userid, "Rain", $level - 1);
					$loc->mintemp = apiDB::getLocationMeasurements($loc->id, $userid, "Mintemp", $level - 1);
				} else {
					$loc = $row["id"];
				}
				array_push($locations, $loc);
			}
		}
		pg_close($conn);
		return $locations;
	}

	static function getLocations($level = 1) {
		$conn = apiDB::getConnection();
		$locations = Array();

		$sql = "SELECT * FROM location ";
		//$sql = "SELECT l.*, ul.userid FROM location l INNER JOIN userlocation ul on l.id = ul.locationid ";
		$result = pg_query($conn, $sql);
		if (count($result) > 0) {
			while($row = pg_fetch_array($result)) {
				if ($level > 0) {
					$loc = new Location();  
					$loc->latitude = $row["latitude"];
					$loc->longitude = $row["longitude"];
					$loc->name = $row["name"];
					$loc->id = $row["id"];
					$loc->userid = $row["userid"];
					$loc->rain = apiDB::getLocationMeasurements($loc->id, $loc->userid, "Rain", $level - 1);
					$loc->mintemp = apiDB::getLocationMeasurements($loc->id, $loc->userid, "Mintemp", $level - 1);
				} else {
					$loc = $row["id"];
				}
				array_push($locations, $loc);
			}
		}
		pg_close($conn);
		return $locations;
	}

	static function getLocation($locationid, $level = 1) {
		$conxn = apiDB::getConnection();

		$location = new Location();
		$sql = "SELECT * FROM location WHERE id = ".$locationid;
		//$sql = "SELECT l.*, ul.userid FROM location l INNER JOIN userlocation ul on l.id = ul.locationid WHERE id = ".$locationid;
		$result = pg_query($conxn, $sql);
		
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			if ($level > 0) {
				$location->latitude = $row["latitude"];
				$location->longitude = $row["longitude"];
				$location->name = $row["name"];
				$location->id = $row["id"];
				$location->userid = $row["userid"];
				$location->rain = apiDB::getLocationMeasurements($locationid, $row["userid"], "Rain", $level - 1);
				$location->mintemp = apiDB::getLocationMeasurements($locationid, $row["userid"], "Mintemp", $level - 1);
			} else {
				$location = $row["id"];
			}
		} 
		pg_close($conxn);
		return $location;
	}
	
	static function getUserLocation($locationid, $userid, $level = 1) {
		$conxn = apiDB::getConnection();

		$location = new Location();
		$sql = "SELECT * FROM location WHERE id = ".$locationid." AND userid = ".$userid;
		//$sql = "SELECT * FROM location l INNER JOIN userlocation ul on l.id = ul.locationid WHERE id = ".$locationid." AND userid = ".$userid;
		$result = pg_query($conxn, $sql);
		if (pg_num_rows($result) > 0) {
			$row = pg_fetch_array($result);
			if ($level > 0) {
				$location->latitude = $row["latitude"];
				$location->longitude = $row["longitude"];
				$location->name = $row["name"];
				$location->id = $row["id"];
				$location->userid = $row["userid"];
				$location->rain = apiDB::getLocationMeasurements($locationid, $userid, "Rain", $level - 1);
				$location->mintemp = apiDB::getLocationMeasurements($locationid, $userid, "Mintemp", $level - 1);
			} else {
                                $location = $row["id"];
                        }
		} 
		pg_close($conxn);
		return $location;
	}

	static function getMeasurement($measurementid, $classname, $level = 1) {
		$conxn = apiDB::getConnection();
		$reflector = new ReflectionClass($classname);
		$measurement = $reflector->newInstance();

		$sql = "SELECT * FROM ".$measurement->tableName()." WHERE id = ".$measurementid;
		$result = pg_query($conxn, $sql);
		if ($result) {
			$row = pg_fetch_array($result);
			if ($level > 0) {
				$measurement->id = $row["id"];
				$measurement->reading = $row[$measurement->columnName()];
				$measurement->fromdate = $row["fromdate"];
				$measurement->todate = $row["todate"];
				$measurement->locationid = $row["locationid"];
				$measurement->note = $row["note"];
			} else {
				$measurement = $row["id"];
			}
		} 
		return $measurement;
		pg_close($conxn);
	}
	
	static function getLocationMeasurements($locationid, $userid, $classname, $month, $year, $level = 1) {
		if (empty($locationid)) {
			return "ERROR: GetLocationMeasurements called without valid location id ";
		}
		if (empty($userid)) {
			return "ERROR: GetLocationMeasurements called without valid user id ";
		}
		$conn = apiDB::getConnection();
		$measurements = Array();
		$reflector = new ReflectionClass($classname);
		$msm = $reflector->newInstance();
		//$sql = "SELECT m.* FROM ".$msm->tableName()." m WHERE m.locationid = ".$locationid;
		$sql = "SELECT m.*, date_part('day', fromdate) as day, ".$msm->columnName()."
			FROM ".$msm->tableName()." m WHERE m.locationid = ".$locationid." 
			and date_part('month', fromdate) = ((".$month." - 1) % 12) + 1
			and date_part('year', fromdate) = ".$year."
			order by 1 ";
		$result = pg_query($conn, $sql);
		if ($result) {
			while($row = pg_fetch_array($result)) {
				if ($level > 0) {
					$msm = $reflector->newInstance();
					$msm->id = $row["id"];
					$msm->reading = $row[$msm->columnName()];
					$msm->fromdate = $row["fromdate"];
					$msm->todate = $row["todate"];
					$msm->locationid = $row["locationid"];
					$msm->userid = $userid;
					$msm->note = $row["note"];
				} else {
					$msm = Array();
					array_push($msm, $row["id"]);
					array_push($msm, $row["day"]);
					array_push($msm, $row[$msm->columnName()]);
				}
				array_push($measurements, $msm);
			}
		}
		pg_close($conn);
		return $measurements;
	}
	
	static function getMaxMeasurementDate($locationid) {
		if (empty($locationid)) {
                        return "ERROR: GetMaxMeasurementDate called without valid location id ";
                }
		$conn = apiDB::getConnection();
		$col = empty($_GET["measure"]) ? "rain" : $_GET["measure"];
                if (!in_array($col, array('rain', 'mintemp'))) {
                        $col = "rain";
                }
                $sql = "SELECT to_char(max(todate + interval '1'), 'YYYY-MM-DD')||'T'||to_char(max(todate + interval '1'),'HH24:MI:SS')  as latestdate FROM ".$col."measurement m WHERE m.locationid = ".$locationid;
		$result = pg_query($conn, $sql);
                if ($result) {
			$row = pg_fetch_array($result);
			return $row["latestdate"];
		}
		return null;
	}
	
	static function addUser(&$user, &$message) {
		if (get_class($user) != "User") {
				$message = "Error, received object other than User";
				return 400;
		}
		if (empty($user->email)) {
			$message = "Error, no email specified for user";
			return 400;
		}
		if (empty($user->password)) {
			$message = "Error, no password specified for user";
			return 400;
		}
		if (empty($user->verified)) $user->verified = 0;        // This is for the case of calling PUT on a user that doesn't exist.
		if (empty($user->access)) $user->access = 1;            //                                  ||
		if (empty($user->sub_summary)) $user->sub_summary = 0;  //                                  ||
		if (empty($user->sub_gwadi)) $user->sub_gwadi = 0;      //                                  ||
		if (empty($user->sub_stats)) $user->sub_stats = 0;      //                                  ||

		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO cw_user (email, password, verified, firstname, lastname, postal, phone, sub_summary, sub_gwadi, sub_stats, access) values ('".$user->email."', '".$user->password."', $user->verified, '".$user->firstname."', '".$user->lastname."', '".$user->postal."', '".$user->phone."', $user->sub_summary, $user->sub_gwadi, $user->sub_stats, $user->access) RETURNING id ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			$message = $rows." User(s) Added";
			return 200;
		} else {
			$message = "Error with insert query : ".pg_last_error($conxn);
			return 400;
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
		$updatestring .= "email = ".(empty($user->email) ? "email" : "'".$user->email."'").", ";
		$updatestring .= "password = ".(empty($user->password) ? "password" : "'".$user->password."'").", ";
		$updatestring .= "firstname = ".(empty($user->firstname) ? "firstname" : "'".$user->firstname."'").", ";
		$updatestring .= "lastname = ".(empty($user->lastname) ? "lastname" : "'".$user->lastname."'").", ";
		$updatestring .= "postal = ".(empty($user->postal) ? "postal" : "'".$user->postal."'").", ";
		$updatestring .= "phone = ".(empty($user->phone) ? "phone" : "'".$user->phone."'").", ";
		$updatestring .= "verified = ".(empty($user->verified) ? "verified" : $user->verified).", ";
		$updatestring .= "sub_summary = ".(empty($user->sub_summary) ? "sub_summary" : $user->sub_summary).", ";
		$updatestring .= "sub_gwadi = ".(empty($user->sub_gwadi) ? "sub_gwadi" : $user->sub_gwadi).", ";
		$updatestring .= "sub_stats = ".(empty($user->sub_stats) ? "sub_stats" : $user->sub_stats).", ";
		$updatestring .= "access = ".(empty($user->access) ? "access" : $user->access);

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
		$sql = "DELETE FROM cw_user WHERE id = ".$userid." RETURNING id; ";
		//$sql = "DELETE FROM userlocation WHERE userid = ".$userid." ; DELETE FROM cw_user WHERE id = ".$userid." RETURNING id; ";
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
		$sql = " DELETE FROM cw_user WHERE email = '".$email."' RETURNING id; ";
		//$sql = "DELETE FROM userlocation WHERE userid = (SELECT id FROM cw_user WHERE email = '".$email."') ; DELETE FROM cw_user WHERE email = '".$email."' RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "User not found" : "User Deleted";
		} else {
			return "Error with delete query : ".pg_last_error($conxn);
		}
	}

	static function verifyUser($userid, $token, &$message) {
		$conxn = apiDB::getConnection();
		$sql = "SELECT email, password, id FROM cw_user WHERE id = " . $userid;
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result);
        		$hashString = $rows[0];
	        	$hashString .= $rows[1];
        		$hashkey = md5($hashString);
		} else {
			$message = "Error with verify query : ".pg_last_error($conxn);
			return 400; //Bad Request
		}
		if (strcmp($hashkey, $token) == 0) {
			error_log("match ".$hashkey);
			$sql = "UPDATE cw_user SET verified = 1 WHERE id = " . $userid;
			$result = pg_query($conxn, $sql);
			if ($result) {
				$rows = pg_affected_rows($result);
				if ($rows < 1) {
					$message = "Could not verify user " . $userid . " ... ";
					return 404; //Not Found
				} else {
					$message = "Account verified";
					//$message = "User " . $userid . " verified";
					return 200;
				}
			} else {
				$message = "Error with verify update query : ".pg_last_error($conxn);
				return 404; //Not Found
			}
		} else {
//error_log("Hash : ".$hashkey);
//error_log("Token: ".$token);
//error_log("HashString: -*".$hashString."*-");
			$message = "Invalid verification token for user " . $userid;
			return 400; //Bad Request
		}
	}
	
	static function addLocation(&$location, &$message) {
		if (get_class($location) != "Location") {
			$message = "Error, received object other than Location";
			return 400;
		}
		if (empty($location->userid)) {
			$message = "Error, no userid specified for location";
			return 400;
		}
		if (empty($location->latitude)) {
			$message = "Error, no latitude specified for location";
			return 400;
		}
		if (empty($location->longitude)) {
			$message = "Error, no longitude specified for location";
			return 400;
		}
		if (empty($location->name)) {
			$message = "Error, no name specified for location";
			return 400;
		}
                $location->latitude = str_replace(",", ".", $location->latitude);
                $location->longitude = str_replace(",", ".", $location->longitude);
		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO location (latitude, longitude, name, userid) values (".$location->latitude.",".$location->longitude.", '".$location->name."', ".$location->userid.") RETURNING location.id ";
		//$sql = "INSERT INTO location (latitude, longitude, name, userid) values (".$location->latitude.",".$location->longitude.", '".$location->name."'); ";
		//$sql .= "INSERT INTO userlocation (userid, locationid) values (".$location->userid.", (SELECT id FROM location WHERE latitude = ".$location->latitude."  and longitude = ".$location->longitude." and name = '".$location->name."')) RETURNING locationid ";
		
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			$message = "Location Added";
			return 200;
		} else { 
			$message = "Error with insert query : ".pg_last_error($conxn);
			error_log($message."\r\n", 3, "/var/tmp/auth.log");
			return 400;
		}
	}
	
	static function updateLocation($locationid, $location) {
		if (get_class($location) != "Location") {
				return "Error, received object other than Location for update";
		}
		$dbLocation = apiDB::getLocation($locationid);
		
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
		$sql = " DELETE FROM rainmeasurement WHERE locationid = ".$locationid." ; DELETE FROM mintempmeasurement WHERE locationid = ".$locationid." ;DELETE FROM location WHERE id = ".$locationid." RETURNING id; ";
		//$sql = "DELETE FROM userlocation WHERE locationid = ".$locationid." ; DELETE FROM rainmeasurement WHERE locationid = ".$locationid." ; DELETE FROM mintempmeasurement WHERE locationid = ".$locationid." ;DELETE FROM location WHERE id = ".$locationid." RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "Location not found: ".$locationid : "Location Deleted: ".$locationid;
		} else {
			return "Error with delete query for location: ".pg_last_error($conxn);
		}
	}
	
	static function addMeasurement($measurement, &$message) {
		if (!is_subclass_of ($measurement, "Measurement")) {
			$message = "Error, received object other than Measurement subclass";
			return 400;
		}
		if (is_null($measurement->reading)) {
			$message = "Error, reading must be specified for measurement";
			return 400;
		}
		if (empty($measurement->locationid)) {
			$message = "Error, no location id specified for measurement";
			return 400;
		}
		if (empty($measurement->fromdate)) {
			$message = "Error, no 'from' date specified for measurement";
			return 400;
		}
		if (empty($measurement->todate)) {
			$message = "Error, no 'to' date specified for measurement";
			return 400;
		}
		$conxn = apiDB::getConnection();
		$sql = "INSERT INTO ".$measurement->tableName()." (fromdate, todate, locationid, ".$measurement->columnName();
		$sql .= empty($measurement->note)? "" : ", note";
		$sql .= ") VALUES (TIMESTAMP '".$measurement->fromdate."', TIMESTAMP '".$measurement->todate."', ".$measurement->locationid.", ".$measurement->reading;
		$sql .= empty($measurement->note)? "" : ", '".$measurement->note."'";
		$sql .= "); ";
		$result = pg_query($conxn, $sql);
//error_log($sql);
//error_log(empty($measurement->note));
//error_log("-".$measurement->note."-");
		if ($result) {
			$rows = pg_affected_rows($result); 
			$message = "Measurement Added: \"".$measurement->columnName()."\"";
			return 200;
		} else { 
			$error = pg_last_error($conxn);
			$message = "Error with ".$measurement->columnName()." measurement insert query : ".$error;
			if (strpos($error, "overlap") !== false) return 418;
			if (strpos($error, "must come before") !== false) return 420;
			return 400;
		}
	}
	
	static function updateMeasurement($measurementid, $measurement) {
		if (!is_subclass_of ($measurement, "Measurement")) {
				return "Error, received object other than Measurement subclass";
		}
		$dbMeasurement = apiDB::getMeasurement($measurementid, get_class($measurement));
		
		if (empty($dbMeasurement->id)) {
			return "Error, Invalid Measurement ID for Update";
		}
		
		$conxn = apiDB::getConnection();
		$updatestring = " set ";
		$updatestring .= $measurement->columnName()." = ".(empty($measurement->reading) ? $measurement->columnName() : $measurement->reading);
		$updatestring .= ", ";
		$updatestring .= "fromdate = ".(empty($measurement->fromdate) ? "fromdate" : "'".$measurement->fromdate."'");
		$updatestring .= ", ";
		$updatestring .= "todate = ".(empty($measurement->todate) ? "todate" : "'".$measurement->todate."'");
		$updatestring .= ", ";
		$updatestring .= "locationid = ".(empty($measurement->locationid) ? "locationid" : $measurement->locationid);
		$updatestring .= ", ";
		$updatestring .= "note = ".(empty($measurement->note) ? "note" : "'".$measurement->note."'");

		$sql = "UPDATE ".$measurement->tableName()." ".$updatestring." WHERE id = ".$measurementid;
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_affected_rows($result); 
			return $rows." ".$measurement->columnName()." measurement(s) updated";
		} else { 
			return "Error with ".$measurement->columnName()." measurement update query : ".pg_last_error($conxn);
		}
	}

	static function deleteMeasurement($measurementid, $table) {
		if (empty($measurementid)) {
			return "Error, no measurement id specified for deleting measurement";
		}
		$conxn = apiDB::getConnection();
		$sql = "DELETE FROM $table WHERE id = ".$measurementid." RETURNING id; ";
		$result = pg_query($conxn, $sql);
		if ($result) {
			$rows = pg_fetch_row($result); 
			$deletedid = $rows[0];
			return empty($deletedid) ? "Measurement not found in $table: ".$measurementid : "Measurement deleted from $table: ".$measurementid;
		} else {
			return "Error with delete query for Measurement in $table: ".pg_last_error($conxn);
		}
	}

 	static function getLatestMeasurements($userid) {
		if (empty($userid)) {
			return "Error, no user id detected or specified for latest measurements";
		}
		$conxn = apiDB::getConnection();
		$sql = "SELECT l.name, l.id AS locationid, r.id, r.rain AS measurement, r.fromdate::date as fromdate, r.todate::date as todate, (2147483640 - extract(epoch from r.created)) AS key, r.created as crtime, 'rain' AS mtype 
                        FROM location l 
	                                LEFT OUTER JOIN rainmeasurement r ON r.locationid = l.id
                        WHERE userid = ".$userid." AND r.created IS NOT NULL
                        UNION
                        SELECT l.name, l.id AS locationid, m.id, m.mintemp AS measurement, m.fromdate::date as fromdate, m.todate::date as todate, (2147483640 - extract(epoch from m.created)) AS key, m.created as crtime, 'mintemp' AS mtype 
                        FROM location l 
	                        LEFT OUTER JOIN mintempmeasurement m ON m.locationid = l.id
                        WHERE userid = ".$userid." AND m.created IS NOT NULL
                        ORDER BY crtime DESC
                        LIMIT 20 ";

                        //FROM location l INNER JOIN userlocation ul ON l.id = ul.locationid 

		$result = pg_query($conxn, $sql);
                $results_array = Array();
		if ($result) {
                        while($row = pg_fetch_array($result)) {
                            $item = Array();
                            $item["locationid"] = $row["locationid"];
                            $item["locationname"] = $row["name"];
                            $item["mid"] = $row["id"];
                            $item["rain"] = $row["mtype"] == "rain" ? $row["measurement"] : "";
                            $item["mintemp"] = $row["mtype"] == "mintemp" ? $row["measurement"] : "";
                            $item["fromdate"] = $row["fromdate"];
                            $item["todate"] = $row["todate"];
                            $item["mtype"] = $row["mtype"];
                            $item["crtime"] = $row["crtime"];
                            //$item[""] = $row[""];
                            //array_push($results_array, $item);
                            //$results_array[$item["locationid"]."_".$item["mtype"]."_".$item["mid"]] = $item;
                            $results_array[$row["key"]] = $item;
                        }
                }
                return $results_array;
        }

	static function graphData($type, $locationid, $period, $graph, $timezone) {
                if (empty($locationid)) {
                        return "ERROR, no measurement id specified for deleting measurement";
                }
                $conxn = apiDB::getConnection();
		$sql = "SELECT CASE WHEN r.".$type." IS NULL THEN 0 ELSE r.".$type." END, d.dt as fdate, CASE WHEN r.days IS NULL THEN 1 ELSE r.days END, CASE WHEN r.d_ave IS NULL THEN 0 ELSE r.d_ave END
			FROM getAllDays(CURRENT_DATE, ".$period.") d left join (
				SELECT ".$type.", cast(todate - interval '8 hours' - interval '1 second' as date) as todate, CEIL(CAST ((EXTRACT(EPOCH FROM todate)-EXTRACT(EPOCH FROM fromdate)) / 86400.0 AS NUMERIC)) AS days,
					ROUND( CAST((".$type."/((EXTRACT(EPOCH FROM todate)-EXTRACT(EPOCH FROM fromdate)) / 86400.0)) AS NUMERIC), 1) AS d_ave
				FROM ".$type."measurement
				WHERE locationid = ".$locationid."
				) r ON d.dt = r.todate
			ORDER BY d.dt DESC ";
//error_log( $sql2);
		$result = pg_query($conxn, $sql);
		$results_array = Array();
		$data = Array();
		if ($result) {
			while($row = pg_fetch_array($result)) {
				for ($i = 0; $i < $row["days"]; $i++) {
					$date = new DateTime($row["fdate"], new DateTimeZone($timezone));
					$date->sub(new DateInterval('P'.$i.'D'));
					$item = Array();
					array_push($item, date_format($date, 'm/d'));
					array_push($item, floatval($row["d_ave"]));
					array_push($data, $item);
					if ($i > 0) pg_fetch_array($result); //otherwise the range shows up in duplicate
				}
			}
			//$datasets = Array();
			//$datasets["type"] = $graph;
			//$datasets["data"] = array_reverse($data);
			//$results_array["JSChart"]["datasets"][0] = $datasets;
		}
		//return $results_array;
		return array_reverse($data);
	}

}
?>
