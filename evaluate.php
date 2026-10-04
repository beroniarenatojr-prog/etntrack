<?php

/*
|--------------------------------------------------------------------------
| THE QR EVALUATION FORM
|--------------------------------------------------------------------------
| A respondent scans an activity's QR code and lands here
| (evaluate.php?activity_id=N). The form shows the ACTIVE questionnaire that
| applies to the activity (questionnaire_for_activity), and the answers come
| back here to be checked and saved, after which the respondent is thanked.
|
| Each device answers an activity's evaluation once. Outside the
| questionnaire's evaluation period, or when it is closed, the form says so
| instead of showing the questions.
|
| Until the database update that adds questionnaires has been applied, the
| old form is used instead (evaluate_legacy.php).
*/

include 'db_connect.php';
require_once 'questionnaire_lib.php';

if (!questionnaires_ready($conn)) {
    include 'evaluate_legacy.php';
    exit;
}

require_once 'questionnaire_form.php';

$posted = $_SERVER['REQUEST_METHOD'] === 'POST';

// Text from the form, never longer than $limit
function evaluation_text($key, $limit){
    $value = $_POST[$key] ?? '';
    return is_scalar($value) ? mb_substr(trim((string)$value), 0, $limit) : '';
}


/* =========================================================
   THE ACTIVITY
========================================================= */

$activity_id = (int)($posted ? ($_POST['activity_id'] ?? 0) : ($_GET['activity_id'] ?? 0));

if ($activity_id <= 0) {
    questionnaire_message_page('Invalid QR code',
        'This link does not say which activity to evaluate. Please scan the QR code again.', 'warning');
    exit;
}

$activity = $conn->query("SELECT * FROM activities WHERE id = $activity_id AND status = 'approved' LIMIT 1")->fetch_assoc();

if (!$activity) {
    questionnaire_message_page('Activity not found',
        'This activity is not open for evaluation. Please check the QR code with the activity coordinator.', 'warning');
    exit;
}

if (!$posted && !empty($_GET['done'])) {
    questionnaire_message_page('Thank you!',
        'Your evaluation has been submitted. Your feedback helps improve the university\'s extension programs.', 'success');
    exit;
}


/* =========================================================
   WHICH QUESTIONNAIRE
   The one the form was opened with, while it is still active
   and still meant for this activity. Otherwise the one that
   applies now.
========================================================= */

$questionnaire = null;
$sent_questionnaire = $posted ? (int)($_POST['questionnaire_id'] ?? 0) : 0;

if ($sent_questionnaire > 0) {

    $questionnaire = $conn->query("SELECT * FROM questionnaires WHERE id = $sent_questionnaire")->fetch_assoc();

    if (!$questionnaire || $questionnaire['status'] !== 'active') {
        questionnaire_message_page('Evaluation closed',
            'This evaluation is closed and no longer accepts responses.', 'info');
        exit;
    }

    $meant_for = $questionnaire['project_id'] === null
        || (int)$questionnaire['project_id'] === (int)($activity['project_id'] ?? 0);

    if (!$meant_for) {
        questionnaire_message_page('Please open the form again',
            'This evaluation form has changed. Please scan the QR code again.', 'warning');
        exit;
    }

} else {

    $questionnaire = questionnaire_for_activity($conn, $activity);

    if (!$questionnaire) {
        questionnaire_message_page('Evaluation not open',
            'This activity is not open for evaluation right now. Please try again later.', 'info');
        exit;
    }
}

$questionnaire_id = (int)$questionnaire['id'];

$period = questionnaire_period_problem($questionnaire);

if ($period) {
    questionnaire_message_page($period[0], $period[1], 'info');
    exit;
}


/* =========================================================
   ONCE PER DEVICE
========================================================= */

$device = questionnaire_device_token();

if (questionnaire_already_answered($conn, $questionnaire_id, $activity_id, $device)) {
    questionnaire_message_page('Already submitted',
        'This device has already submitted an evaluation for this activity. Thank you for your feedback!', 'success');
    exit;
}


/* =========================================================
   A SUBMITTED FORM: CHECK, SAVE, THANK
========================================================= */

$structure = questionnaire_structure($conn, $questionnaire_id, true);
$old = array();
$errors = array();
$notice = '';

if ($posted) {

    $thank_you = 'evaluate.php?activity_id='.$activity_id.'&done=1';

    // Only spam robots fill in the hidden field; they are thanked and nothing is kept
    if (evaluation_text('website', 200) !== '') {
        header('Location: '.$thank_you, true, 303);
        exit;
    }

    $old = $_POST;
    $result = questionnaire_collect_answers($questionnaire, $structure, $_POST);

    if ($result['errors']) {

        $errors = $result['errors'];

    } elseif ($result['answered'] === 0) {

        $notice = 'Please answer the questions before submitting.';

    } else {

        $types = questionnaire_respondent_types();
        $type = evaluation_text('respondent_type', 30);

        $saved = questionnaire_save_response($conn, $questionnaire, $activity, array(
            'name' => evaluation_text('evaluator_name', 100),
            'type' => isset($types[$type]) ? $type : '',
            'barangay' => evaluation_text('barangay', 100),
            'comments' => evaluation_text('comments', 5000),
            'device' => $device
        ), $result['answers']);

        // Saved, or already saved by a double tap: either way, thank them
        if ($saved !== false) {
            header('Location: '.$thank_you, true, 303);
            exit;
        }

        $notice = 'Your evaluation could not be saved. Please try submitting again.';
    }
}


/* =========================================================
   THE FORM
========================================================= */

$activity_date = '';

if (!empty($activity['activity_date'])) {
    $timestamp = strtotime($activity['activity_date']);
    $activity_date = $timestamp !== false ? date('F d, Y', $timestamp) : $activity['activity_date'];
}

questionnaire_page_start('ISU Extension & Training Services Evaluation Form');

questionnaire_render_form($questionnaire, $structure, array(
    'mode' => 'public',
    'action' => 'evaluate.php?activity_id='.$activity_id,
    'hidden' => array('activity_id' => $activity_id, 'questionnaire_id' => $questionnaire_id),
    'activity' => array(
        'title' => $activity['activity_name'] ?? '',
        'date' => $activity_date,
        'venue' => $activity['venue'] ?? ''
    ),
    'old' => $old,
    'errors' => $errors,
    'notice' => $notice
));

questionnaire_page_end();
