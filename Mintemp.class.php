<?php
require_once("Measurement.class.php");
require_once("apiDB.php");             

class Mintemp extends Measurement
{
	public function columnName() {
		return "mintemp";
	}
	
	public function tableName() {
		return "mintempmeasurement";
	}
	
}