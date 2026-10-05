<?php

// Every page works in Philippine time, including the ones that do not load
// header.php (the public evaluation form, file downloads, the QR page), so
// "today" is the same day everywhere: questionnaire periods, activity days.
date_default_timezone_set('Asia/Manila');

// On XAMPP this connects to the local database. Anywhere else it reads the
// live login from db_config.php, which is kept out of git so the password is
// never published to the public GitHub repository.
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']);

if ($is_local) {

    $conn = new mysqli('localhost', 'root', '', 'xtntrack');

} else {

    $config_file = __DIR__ . '/db_config.php';

    if (!file_exists($config_file)) {
        http_response_code(500);
        die("Setup needed: db_config.php is missing on the server. Upload it next to index.php.");
    }

    $config = require $config_file;

    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database']
    );
}

if ($conn->connect_error) {
    http_response_code(500);
    die("Could not connect to the database.");
}
