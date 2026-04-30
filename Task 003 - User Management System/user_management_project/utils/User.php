<?php

namespace Utils;

class User
{
  public string $username;
  public string $email;
  public string $password;

  public function __construct(string $username, string $email, string $password)
  {
    $this->username = $username;
    $this->email    = $email;
    $this->password = $password;
  }
  
  public function getProfile(): string
  {
    return "Username : {$this->username}<br>
            Email    : {$this->email}<br>
            Password : {$this->password}";
  }
}
