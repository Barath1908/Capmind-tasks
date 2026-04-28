<?php 
function validateVital(array $vitalData,callable $ruleFunction): array {
 return $ruleFunction($vitalData);
}
?>