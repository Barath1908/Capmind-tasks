<?php

function printSectionHeader(string $title): void
{
  printDivider();
  echo strtoupper($title) . '<br>';
  printDivider();
}
 

function printValidationRow(string $label, string $status, string $message): void
{
  echo "  {$label} : {$status}  ({$message})" . '<br>';
}
 

function printDivider(int $width = 58): void
{
  echo str_repeat('-', $width) . '<br>';
}