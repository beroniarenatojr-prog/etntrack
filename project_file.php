<?php
/*
| Hands over a document attached to a project, but only to someone allowed to
| see it. The files themselves sit in uploads/documents/ under random names
| and that folder refuses to run scripts, so nothing there is reachable by
| guessing a URL.
|
|   project_file.php?type=proposal&id=12            view in the browser
|   project_file.php?type=proposal&id=12&download=1 save to the computer
*/

session_start();

if(empty($_SESSION['login_id'])){
    http_response_code(401);
    exit("Please log in first.");
}

include 'db_connect.php';

$tables = array(
    'assessment'  => array('assessment_reports', 'Assessment Report'),
    'moa'         => array('memorandum_agreements', 'MOA'),
    'capsule'     => array('capsules', 'Capsule'),
    'proposal'    => array('proposals', 'Proposal'),
    'preparation' => array('conduct_preparations', 'Conduct Preparation'),
    'designation' => array('designations', 'Designation')
);

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if(!isset($tables[$type]) || $id <= 0){
    http_response_code(404);
    exit("File not found.");
}

list($table, $label) = $tables[$type];

$row = $conn->query("
    SELECT d.file_name, p.faculty_id, p.created_by, p.title
    FROM `$table` d
    INNER JOIN projects p ON p.id = d.project_id
    WHERE d.id = $id
")->fetch_assoc();

if(!$row || empty($row['file_name'])){
    http_response_code(404);
    exit("File not found.");
}

// The admin sees every project; a coordinator only their own
$user = (int)$_SESSION['login_id'];
$is_admin = ($_SESSION['login_type'] ?? 0) == 1;
$own = (int)$row['faculty_id'] === $user || (int)$row['created_by'] === $user;

if(!$is_admin && !$own){
    http_response_code(403);
    exit("This file belongs to someone else's project.");
}

$path = __DIR__.'/uploads/documents/'.basename($row['file_name']);

if(!is_file($path)){
    http_response_code(404);
    exit("The file is missing from the server.");
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

$mime_types = array(
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
);

// A name that means something to whoever downloads it
$safe_title = preg_replace('/[^A-Za-z0-9 _-]/', '', $row['title']);
$filename = trim($label.' - '.$safe_title).'.'.$extension;

// Anything that is not a PDF or a picture is always downloaded, never opened here
$inline = empty($_GET['download']) && in_array($extension, array('pdf','jpg','png','webp'));

header('Content-Type: '.($mime_types[$extension] ?? 'application/octet-stream'));
header('Content-Length: '.filesize($path));
header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($path);
