<?php
function scanFolder(string $path): void
{
  if (!is_dir($path) || !is_readable($path)) {
    echo  "[Cannot read: $path]<br>";
    return;
  }

  $entries = scandir($path);             

  foreach ($entries as $entry) {
      
    if ($entry === '.' || $entry === '..') {
      continue;
    }

    $fullPath = $path . DIRECTORY_SEPARATOR . $entry;

    if (is_dir($fullPath)) {
      echo  "Folder: $entry/";
      scanFolder($fullPath);   
    } else {  
      echo  "File: $entry<br>";
    }
  }
}
?>