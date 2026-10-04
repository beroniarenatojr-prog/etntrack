<?php

/*
|--------------------------------------------------------------------------
| QUESTIONNAIRE PREVIEW (administrators)
|--------------------------------------------------------------------------
| The evaluation form exactly as respondents will see it, drawn by the same
| code as the QR form (questionnaire_form.php), for a questionnaire in any
| status. The answers can be tried out, but nothing is ever sent or saved.
|
|   questionnaire_preview.php?id=N
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php';
require_once 'questionnaire_form.php';

header('X-Robots-Tag: noindex');

if (empty($_SESSION['login_id']) || (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(empty($_SESSION['login_id']) ? 401 : 403);
    questionnaire_message_page('Sign in needed', 'Only administrators can preview questionnaires.', 'warning');
    exit;
}

if (!questionnaires_ready($conn)) {
    questionnaire_message_page('Database update needed',
        'Apply the latest database update in System Update to preview questionnaires.', 'info');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$questionnaire = $id > 0 ? $conn->query("SELECT * FROM questionnaires WHERE id = $id")->fetch_assoc() : null;

if (!$questionnaire) {
    http_response_code(404);
    questionnaire_message_page('Questionnaire not found', 'It may have been deleted.', 'warning');
    exit;
}

$structure = questionnaire_structure($conn, $id, true);

if (!$structure['sections']) {
    questionnaire_message_page('Nothing to preview yet',
        'Add a section with at least one question, then come back to the preview.', 'info');
    exit;
}

// The activity's details are filled in from the QR code; show where they go
$project = null;

if (!empty($questionnaire['project_id'])) {
    $project = $conn->query("SELECT title FROM projects WHERE id = ".(int)$questionnaire['project_id'])->fetch_assoc();
}

questionnaire_page_start('Preview: '.$questionnaire['title']);

questionnaire_render_form($questionnaire, $structure, array(
    'mode' => 'preview',
    'activity' => array(
        'title' => $project ? $project['title'].' (an activity of this project)' : 'The name of the activity appears here',
        'date' => date('F d, Y'),
        'venue' => 'The venue appears here'
    )
));

questionnaire_page_end();
