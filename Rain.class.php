<?php
require_once("Measurement.class.php");
require_once("apiDB.php");             

class Rain extends Measurement
{
	public function columnName() {
		return "rain";
	}
	
	public function tableName() {
		return "rainmeasurement";
	}
	
}