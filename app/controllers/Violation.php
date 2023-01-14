<?php
include_once "../models/Violations.php";

header("Content-Type: application/json");

if(isset($_POST)) {
	$violationObject = new Violations();

	$violations = $violationObject->getViolations();
	$returnJSON = json_encode($violations);
	
	echo $returnJSON;
}

?>
