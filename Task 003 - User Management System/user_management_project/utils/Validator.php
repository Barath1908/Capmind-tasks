<?php


namespace Utils;

class Validator
{

  public function validateUsername(string $username): array
  {
    if (strlen($username) < 4 ) {
      return [
          'status'  => 'Invalid',
          'message' => "Too short (min 4 chars)",
      ];
    }

    return ['status' => 'Valid', 'message' => 'Looks good'];
  }

  
  public function validateEmail(string $email): array
  {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'Valid', 'message' => 'Looks good'];
    }

    return ['status' => 'Invalid', 'message' => 'Not a valid e-mail format'];
  }


  public function validatePassword(string $password): array
  {
    if (strlen($password) < 6 ) {
      return [
        'status'  => 'Weak',
        'message' => "Too short (min 6 chars)",
      ];
    } else {
      return [
        'status' => 'Strong',
        'message' => 'Excellent password'
      ];
    }

  }
}
