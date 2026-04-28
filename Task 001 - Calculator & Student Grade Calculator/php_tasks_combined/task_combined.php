<?php
// Part 1: Simple Calculator Using Functions
$a = 25;
$b = 5;

function add($a, $b) {
    echo "Addition: " . $a + $b . "<br>";
}

function subtract($a, $b) {
    echo "Subtraction: " . $a - $b . "<br>";
}

function multiply($a, $b) {
    echo "Multiplication: " . $a * $b . "<br>";
}

function divide($a, $b) {
    echo "Division: " . $a / $b . "<br><br>";
}

echo "----- Calculator Results -----<br>";
add($a, $b);
subtract($a, $b);
multiply($a, $b);
divide($a, $b);

// Part 2: Student Grade Calculator
$mark1 = 78;
$mark2 = 85;
$mark3 = 90;

$total = $mark1 + $mark2 + $mark3;
$average = $total / 3;

if ($average >= 90) {
    $grade = "A";
} elseif ($average >= 75) {
    $grade = "B";
} elseif($average >= 50) {
    $grade = "C";
} else {
    $grade = "Fail";
}

echo "----- Student Grade Calculation -----<br>";
echo "Total Marks: " . $total . "<br>";
echo "Average: " . number_format($average, 2) . "<br>";
echo "Grade: " . $grade . "<br>";
?>