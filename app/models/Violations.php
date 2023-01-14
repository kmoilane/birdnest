<?php
require_once(__DIR__."/../config/config.php");
require_once(__DIR__."/../classes/db.php");

class Violations extends Database
{
	private $db;

	/*
	** Checks if a drone is already in a violations table. Returns violation
	** data if it exists and false if not.
	*/

	public function checkViolation($droneSNo)
	{
		$this->db = new Database;
		$this->db->prepareQuery("SELECT * FROM violations WHERE drone_serial_number = :droneSNo");

		$this->db->bind(":droneSNo", $droneSNo);

		$this->db->execute();

		if ($this->db->rowCount() > 0)
		{
			return $this->db->single();
		}
		else
		{
			return false;
		}

	}

	/*
	** Gets every row from the violations table and returns it
	**
	*/

	public function getViolations()
	{
		$this->db = new Database;
		$this->db->prepareQuery("SELECT * FROM violations ORDER BY last_seen DESC");
		return $this->db->resultSet();
	}

	/*
	** Inserts pilot and drone information to violations table. Required fields
	** in $data array are droneSNo, pilotFname, pilotLname, pilotEmail,
	** pilotPhone, distance, positionX adn positionY
	*/
	public function addViolation($data)
	{
		$this->db = new Database;
		$this->db->prepareQuery(
		"INSERT INTO violations (
			drone_serial_number, pilot_fname, pilot_lname,
			pilot_email, pilot_phone_number,
			closest_distance, closest_x, closest_y)
		VALUES (
			:droneSNo, :pilotFname, :pilotLname,
			:pilotEmail, :pilotPhone, :distance,
			:positionX, :positionY)"
		);

		$this->db->bind(":droneSNo", $data["droneSNo"]);
		$this->db->bind(":pilotFname", $data["pilotFname"]);
		$this->db->bind(":pilotLname", $data["pilotLname"]);
		$this->db->bind(":pilotEmail", $data["pilotEmail"]);
		$this->db->bind(":pilotPhone", $data["pilotPhone"]);
		$this->db->bind(":distance", $data["distance"]);
		$this->db->bind(":positionX", $data["positionX"]);
		$this->db->bind(":positionY", $data["positionY"]);

		if ($this->db->execute())
			return true;
		else
			return false;
	}

	/*
	** Updates the closest_distance field of a drone in the violation table
	*/
	public function updateViolationDistance($droneSNo, $distance, $posX, $posY)
	{
		$this->db = new Database;
		$this->db->prepareQuery(
			"UPDATE violations
			SET closest_distance = :distance,
				closest_x = :positionX,
				closest_y = :positionY
			WHERE drone_serial_number = :droneSNo");

		$this->db->bind(":distance", $distance);
		$this->db->bind(":droneSNo", $droneSNo);
		$this->db->bind(":positionX", $posX);
		$this->db->bind(":positionY", $posY);

		if($this->db->execute())
			return true;
		else
			return false;
	}

	/*
	**  Updates the last_seen field of a drone in the violation table
	*/
	public function updateViolationTime($droneSNo)
	{
		$this->db = new Database;
		$this->db->prepareQuery("UPDATE violations SET last_seen = SYSDATE() WHERE drone_serial_number = :droneSNo");

		$this->db->bind(":droneSNo", $droneSNo);

		if($this->db->execute())
			return true;
		else
			return false;
	}

	/*
	** Deletes a row from violation table if it hasn't been seen in 10 minutes
	*/
	public function deleteOldViolations()
	{
		$this->db = new Database;
		$this->db->prepareQuery("DELETE FROM violations WHERE last_seen < (NOW() - INTERVAL 10 MINUTE)");

		if($this->db->execute())
			return true;
		else
			return false;
	}

}

?>
