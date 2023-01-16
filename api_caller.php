<?php
include_once "app/models/Violations.php";
print("Fetching data from API\n");

/*
** Since we want to keep our database up to date and actually show every violtaion from the last 10 minutes,
** we will loop these functions every 2 seconds even if no client is open.
*/

while (true)
{
    getSnapshots();
    sleep(2);
}

/*
** Calculates the Euclidean distance between the nest and the drone using The Pythagorean theorem.
** This is used to find drones that flew too close to the birdnest.
*/

function calculateDistance($droneX, $droneY)
{
	$nestX = 250000;
	$nestY = 250000;
	$distance = 0;

	$distance = sqrt(pow($droneX - $nestX, 2) + pow($droneY - $nestY, 2));
	return($distance);
}

/*
** Gets pilot data from Reaktors pilot endpoint in a JSON format.
** This function is only called when someone violated the no fly zone.
** If call fails, we try again in 200ms.
*/

function getPilotData($droneSerialNumber)
{
	$url = "https://assignments.reaktor.com/birdnest/pilots/".$droneSerialNumber;
	$headers = get_headers($url);
	$httpCode = substr($headers[0], 9, 3);
	if ($httpCode != "404")
	{
		do {
			$response = @file_get_contents($url, false);
			usleep(2000);
		}
		while (!$response);
		$pilotData = json_decode($response, true);

		return($pilotData);
	}

	return null;
}

/*
** Handles drone violations, by either updating the closest distance, last seen time or adds a new violation.
** Also deletes violations that are older than 10 minutes, because we don't want to keep them.
*/

function handleViolation($drone, $dist)
{
	$Violation = new Violations();
	
	if ($pilotData = $Violation->checkViolation($drone->serialNumber))
	{
		if ($dist < $pilotData->closest_distance)
			$Violation->updateViolationDistance(
				$drone->serialNumber, $dist, $drone->positionX, $drone->positionY);
		else
			$Violation->updateViolationTime($drone->serialNumber);
	}
		
	else if (!is_null($pilotData = getPilotData($drone->serialNumber)))
	{
		$data = [
			"droneSNo"      =>  $drone->serialNumber,
			"pilotFname"    =>  $pilotData["firstName"],
			"pilotLname"    =>  $pilotData["lastName"],
			"pilotEmail"    =>  $pilotData["email"],
			"pilotPhone"    =>  $pilotData["phoneNumber"],
			"distance"      =>  $dist,
			"positionX"     =>  $drone->positionX,
			"positionY"     =>  $drone->positionY
		];
		$Violation->addViolation($data);
	}

	$Violation->deleteOldViolations();
}

/*
** parseDrones function loops through the snapshot data and looks for drones that flew too close to the nest.
*/

function parseDrones($snapshot)
{
	foreach ($snapshot->capture->drone as $drone)
	{
		$dist = calculateDistance($drone->positionX, $drone->positionY);

		if ($dist <= 100000 && $drone->serialNumber !== NULL)
			handleViolation($drone, $dist);
	}
}

/*
** Calls with curl to Reaktors drones API which returns XML snapshot of the drones currently in the area.
** If the response code is 200, and snapshot is successfully aquired, we interpret it into an object, and
** proceed to parse that data.
*/

function getSnapshots()
{
	$url = "assignments.reaktor.com/birdnest/drones";
	$curl = curl_init($url);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
	$response = curl_exec($curl);	
	$httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	
	curl_close($curl);
	
    if ($httpCode == 200)
	{
		$snapshotObject = simplexml_load_string($response);
		parseDrones($snapshotObject);
	}
}

?>

