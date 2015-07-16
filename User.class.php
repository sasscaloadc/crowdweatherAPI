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
		return apiDB::getUsers();
	}

	public function put_array($array) {
		
		$user = new User();
		
		if (!empty($array["id"])) {
			apiDB::getUser($array["id"], $user);
		} else {
			if (!empty($array["email"])) {
				apiDB::getUserByEmail($array["email"], $user);
			}
		}
		$user->email = empty($array["email"]) ? $user->email : $array["email"];
		$user->password = empty($array["password"]) ? $user->password : $array["password"];
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
		
		return apiDB::addUser($user);
	}
	
	public function delete_array($array) {
		if (!empty($array["id"])) {
			return apiDB::deleteUser($array["id"]);
		} else {
			if (!empty($array["email"])) {
				return apiDB::deleteUserByEmail($array["email"]);
			}
		}
		return "ERROR: No user ID or email specified for deletetion";
	}
	
	public function getInstanceDetails($id) {
		apiDB::getUser($id, $this);
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
	
}

?>
