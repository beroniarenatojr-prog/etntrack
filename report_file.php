<?php
/*
| Hands over a Progress or Terminal Report file, but only to someone allowed
| to see it: the Extension Office, the coordinator who uploaded it, or the
| coordinator of the project it belongs to. The files sit in uploads/reports/,
| which refuses direct requests, so they are only reachable through here.
|
|   report_file.php?id=12              view in the browser (PDF)
|   report_file.php?id=12&download=1   save to the computer
*/

session_start();

if(empty($_SESSION['login_id'])){
    http_response_code(401);
    exit("Please log in first.");
}

include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

$has_project = $conn->query("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'uploaded_reports' AND COLUMN_NAME = 'project_id'
")->num_rows > 0;

$row = $id > 0 ? $conn->query("
    SELECT r.report_title, r.report_type, r.file_name, r.uploaded_by".($has_project ? ",
        p.faculty_id AS project_faculty, p.created_by AS project_creator" : "")."
    FROM uploaded_reports r
    ".($has_project ? "LEFT JOIN projects p ON p.id = r.project_id" : "")."
    WHERE r.id = $id
")->fetch_assoc() : null;

if(!$row || empty($row['file_name'])){
    http_response_code(404);
    exit("Report not found.");
}

$user = (int)$_SESSION['login_id'];
$is_admin = ($_SESSION['login_type'] ?? 0) == 1;
$allowed = $is_admin
    || (int)$row['uploaded_by'] === $user
    || (int)($row['project_faculty'] ?? 0) === $user
    || (int)($row['project_creator'] ?? 0) === $user;

if(!$allowed){
    http_response_code(403);
    exit("This report belongs to someone else.");
}

$path = __DIR__.'/uploads/reports/'.basename($row['file_name']);

if(!is_file($path)){
    http_response_code(404);
    exit("The file is missing from the server.");
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime_types = array(
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
);

// A name that means something to whoever downloads it
$safe_title = trim(preg_replace('/[^A-Za-z0-9 _-]/', '', $row['report_type'].' - '.$row['report_title']));
$filename = ($safe_title !== '' ? $safe_title : 'Report').'.'.$extension;

// Only a PDF opens in the browser; anything else is always downloaded
$inline = empty($_GET['download']) && $extension === 'pdf';

header('Content-Type: '.($mime_types[$extension] ?? 'application/octet-stream'));
header('Content-Length: '.filesize($path));
header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
