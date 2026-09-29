<?php

// Local XAMPP when browsing via localhost, Hostinger everywhere else
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']);

if ($is_local) {
    $conn = new mysqli('localhost', 'root', '', 'xtntrack');
} else {
    $conn = new mysqli(
        'localhost',
        'u988863428_xtntrack1',
        'Xtntrack-1',
        'u988863428_xtntrack123'
    );
}

if ($conn->connect_error) {
    die("Could not connect to MySQL: " . $conn->connect_error);
}
