<?php

require_once 'utils/User.php';       
require_once 'utils/Validator.php';  
 
include_once 'utils/helpers.php';   
 
$users = require_once 'data/users.php';
 
use Utils\User;
use Utils\Validator as UserValidator;   
 
echo '<br>';
printDivider();
echo '  USER MANAGEMENT SYSTEM - PHP OOP' . '<br>';
printDivider();
echo '<br>';
 
$validator = new UserValidator();   
 
foreach ($users as $index => $data) {
 
  $user = new User(
    $data['username'],
    $data['email'],
    $data['password']
  );

  printSectionHeader('User #' . ($index + 1) . ' — ' . $user->username);

  echo $user->getProfile() . '<br>';
  printDivider();

  $usernameResult = $validator->validateUsername($user->username);
  $emailResult    = $validator->validateEmail($user->email);
  $passwordResult = $validator->validatePassword($user->password);

  printValidationRow('Username', $usernameResult['status'], $usernameResult['message']);
  printValidationRow('Email',    $emailResult['status'],    $emailResult['message']);
  printValidationRow('Password', $passwordResult['status'], $passwordResult['message']);
  printDivider();

  echo '<br>';
}
 

