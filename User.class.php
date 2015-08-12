<?php
require_once("RESTObject.class.php");
require_once("Location.class.php");
require_once("apiDB.php");             // This sucks a LOT

class User extends RESTObject
{
	public $email = '';
	public $password = '';
	public $locations = Array();
	
	public function get_array_instance() {
		$array = Array();
		$array["email"] = $this->email;
		$array["password"] = $this->password;
		$array["id"] = $this->id;
		$array["locations"] = $this->locations;
		return $array;
	}
	
	public function get_array_all() {
		if ($this->access > 1) {
			return apiDB::getUsers();
		} else {                 // only return the user that you have access to
			$users = Array();
			$user = apiDB::getUserByEmail( $_SERVER['PHP_AUTH_USER'] );
			array_push($users, $user);
			return $users; 
		}
	}

	public function put_array($array) {
		
		if ($this->access < 1) {
			return "Not authorized to make any updates : guest account";
		}
		$user = new User();
		
		if (!empty($array["id"])) {
			$user = apiDB::getUser($array["id"]);
			if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
				return "Not authorized to update User ".$user->id;
			}
		} else {
			if (!empty($array["email"])) {
				$user = apiDB::getUserByEmail($array["email"]);
				if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
					return "Not authorized to update User ".$user->id;
				}
			}
		}
		$user->email = empty($array["email"]) ? $user->email : $array["email"];
		$user->password = empty($array["password"]) ? $user->password : $array["password"];
		if ($this->access > 1) {
			$user->access = empty($array["access"]) ? $user->access : $array["access"]; // not sure if this is still a security flaw... 
		}
		if (empty($user->id)) {
			return apiDB::addUser($user);
		} else {
			return apiDB::updateUser($user->id, $user);
		}
		
	}
	
	public function post_array($array) {
		$user = new User();
		
		$user->email = $array["email"];
		$user->password = $array["password"];
		// access is 1 by database default
	
		//NOTE: No access restrictions. Anyone with a login (also guest:guest) can add a newuser	
		return apiDB::addUser($user);
	}
	
	public function delete_array($array) {
		if (!empty($array["id"])) {
			$user = apiDB::getUser($array["id"]);
			if ($this->access <= 1) {
				return "Not authorized to delete User ".$user->id;
			} else {
				return apiDB::deleteUser($array["id"]);
			}
		} else {
			if (!empty($array["email"])) {
				if ($this->access <= 1) {
					return "Not authorized to delete User ".$array["email"];
				} else {
					return apiDB::deleteUserByEmail($array["email"]);
				}
			}
		}
		return "ERROR: No user ID or email specified for deletion";
	}
	
	public function getInstanceDetails($id) {
		$user = apiDB::getUser($id, 2);
		if (empty($user->id)) {
			return self::NO_SUCH_ID;
		}
		if (($_SERVER['PHP_AUTH_USER'] != $user->email) && ($this->access <= 1)) {
			return self::ACCESS_DENIED;
		}
		$this->id = $user->id;
		$this->email = $user->email;
		$this->password = $user->password;
		$this->locations = $user->locations;  // Does this array need cloning?
		// 	Preserving $this->access however, to retain admin rights.
		return self::SETUP_OK;
	}
	
	/**
     * Locations Endpoint
     */
     protected function locations() {
		if (empty($this->id)) {
			return $this->_response("User id not set. Cannot run \"locations\" without a valid user id.", 404);	
		}
		$loc = new Location();
		$loc->setupClass($this->args, $this->access, $this->extension);
		$loc->userid = $this->id;
		return $loc->process();
	 }

	function apiLink() {
		$linkString = "https://".apiDB::$servername."/".apiDB::dirname()."/users/" . $this->id ;
		return "<a href=\"".$linkString."\">".$this->email."</a>";
	}

     protected function latestmeasurements() {
         $userid = $this->id;
         if (empty($this->id)) {
            $user = apiDB::getUserByEmail($_SERVER['PHP_AUTH_USER']);
	    $userid = $user->id;
         }	
         return $this->display(apiDB::getLatestMeasurements($userid));
     }

     protected function authenticate () {
                return $_SERVER['PHP_AUTH_USER'];
     }

}


?>
