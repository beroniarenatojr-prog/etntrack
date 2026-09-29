<?php

$conn = new mysqli(
    'localhost',
    'u988863428_Xtntrack123',
    'Xtntrack-1',
    'u988863428_xtntrack1'
);

if ($conn->connect_error) {
    die("Could not connect to MySQL: " . $conn->connect_error);
}