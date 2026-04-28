<?php
function checkTemperature(array $vitalData): array
{
  $temp = (float) $vitalData['value'];

  if ($temp < 97.0){
    $vitalData['status']  = 'LOW';
    $vitalData['message'] = 'Hypothermia suspected - temperature below normal';
  } elseif ($temp > 99.5){
    $vitalData['status']  = 'HIGH';
    $vitalData['message'] = 'Fever detected - temperature above normal';
  } else {
    $vitalData['status']  = 'NORMAL';
    $vitalData['message'] = 'Temperature within normal range';
  }
  return $vitalData;
}

function checkPulse(array $vitalData): array
{
  $pulse = (int) $vitalData['value'];

  if ($pulse < 60) {
    $vitalData['status']  = 'LOW';
    $vitalData['message'] = 'Bradycardia detected - pulse rate low';
  } elseif ($pulse > 100) {
    $vitalData['status']  = 'HIGH';
    $vitalData['message'] = 'Tachycardia detected - pulse rate high';
  } else {
    $vitalData['status']  = 'NORMAL';
    $vitalData['message'] = 'Pulse rate within normal range';
  }

  return $vitalData;
}

function checkBloodPressure(array $vitalData): array
{
  $parts    = explode('/', (string) $vitalData['value']); // example 120/80 -> ['120','80']
  $upperValue = (int) $parts[0];

  if ($upperValue < 90) {
    $vitalData['status']  = 'LOW';
    $vitalData['message'] = 'Hypotension detected - blood pressure low';
  } elseif ($upperValue > 120) {
    $vitalData['status']  = 'HIGH';
    $vitalData['message'] = 'Hypertension detected - blood pressure high';
  } else {
    $vitalData['status']  = 'NORMAL';
    $vitalData['message'] = 'Blood pressure within normal range';
  }

  return $vitalData;
}
?>