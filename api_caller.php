<?php
include_once "app/models/Violations.php";
print("Fetching data from API\n");

while (true)
{
    getSnapshots();
    sleep(2);
}

function calculateDistance($positionX, $positionY)
{
	$centerX = 250000;
	$centerY = 250000;
	$distance = 0;

	$distance = sqrt(pow($positionX - $centerX, 2) + pow($positionY - $centerY, 2));
	return($distance);
}

function getPilotData($serial_number)
{
    do {
	    $json_data = @file_get_contents('https://assignments.reaktor.com/birdnest/pilots/'.$serial_number, false);
        usleep(2000);
    }
    while (!$json_data);
	$pilot_data = json_decode($json_data, true);

	return($pilot_data);
}

function logViolation($data, $Violation)
{
	$violation = $Violation->checkViolation($data["droneSNo"]);

	if ($violation === false)
	{
		$Violation->addViolation($data);
		$Violation->deleteOldViolations();
		return;
	}

	$Violation->deleteOldViolations();
}

/*
** parseDrones function loops through the snapshot data and looks for drones that flew too close to the nest.
** It either calls a function to: A) update the closest distance to the nest, B) update the time of the last violation,
** C) add a new violation to the database
*/

function parseDrones($snapshot)
{
    $Violation = new Violations();
	foreach ($snapshot->capture->drone as $drone)
	{
		$dist = calculateDistance($drone->positionX, $drone->positionY);

		if ($dist > 100000 || $drone->serialNumber == NULL)
			continue;

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
			logViolation($data, $Violation);
		}
	}
	$Violation->deleteOldViolations();
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

