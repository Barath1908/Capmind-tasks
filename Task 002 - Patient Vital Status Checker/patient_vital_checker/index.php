<?php

include 'vitals.php';
include 'validate.php';
include 'rules.php';
include 'scanner.php';

$ruleMap = [
  'Temperature' => 'checkTemperature',
  'Pulse'       => 'checkPulse',
  'BP'          => 'checkBloodPressure',
];
 

echo "   PATIENT VITAL STATUS CHECKER — EMR   <br>";
 

foreach ($patientsData as $record) {
 
  $type = $record['vital_type'];

 
  if (!isset($ruleMap[$type])) {
      echo "Patient : {$record['patient_name']}<br>";
      echo "Vital   : $type<br>";
      echo "Status  : UNKNOWN — no rule defined for this vital type<br>";
      echo "----------------------------------------<br>";
      continue;
  }

  $callback = $ruleMap[$type];         

 
  $result = validateVital($record, $callback);

 
  echo "Patient : {$result['patient_name']}<br>";
  echo "Vital   : {$result['vital_type']}<br>";
  echo "Value   : {$result['value']}<br>";
  echo "Status  : {$result['status']}<br>";
  echo "Message : {$result['message']}<br>";
  echo "----------------------------------------<br>";
}
 

echo "<br>Project Files:<br>";
scanFolder(__DIR__);
echo "<br>";
?>
