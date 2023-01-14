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

function logViolation($data, $violationObject)
{
	$violation = $violationObject->checkViolation($data["droneSNo"]);

	if ($violation === false)
	{
		$violationObject->addViolation($data);
		$violationObject->deleteOldViolations();
		return;
	}

	$violationObject->deleteOldViolations();
}

function parseDrones($snapshot, $violationObject)
{
    $violationObject = new Violations();
	foreach ($snapshot->capture->drone as $drone)
	{
		$dist = calculateDistance($drone->positionX, $drone->positionY);

		if ($dist > 100000 || $drone->serialNumber == NULL)
			continue;

        if ($pilotData = $violationObject->checkViolation($drone->serialNumber)) {
            if ($dist < $pilotData->closest_distance)
		        $violationObject->updateViolationDistance(
                    $drone->serialNumber, $dist, $drone->positionX, $drone->positionY);
            else
                $violationObject->updateViolationTime($drone->serialNumber);
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
			logViolation($data, $violationObject);
		}
	}
}

function getSnapshots()
{
	$url = "assignments.reaktor.com/birdnest/drones";
	$curl = curl_init($url);

    $violationObject = new Violations();
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPGET, true);
	
	$response = curl_exec($curl);	
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
	curl_close($curl);
	
    if ($httpCode == 200)
	{
		$snapshot = simplexml_load_string($response);
		parseDrones($snapshot, $violationObject);
	}
}

?>

