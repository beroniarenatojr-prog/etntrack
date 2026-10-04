<?php
/*
| Where the evaluation form used to send its answers. The form now sends them
| to evaluate.php, which checks and saves every question type; a form opened
| before that change still arrives here and is handled there the same way.
|
| Until the database update that adds questionnaires has been applied, the
| old form (evaluate_legacy.php) sends its ratings here and they are saved
| the old way, below.
*/
include 'db_connect.php';
require_once 'questionnaire_lib.php';

if (questionnaires_ready($conn)) {
    include 'evaluate.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die("Invalid request.");
}

$activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
$evaluator_name = isset($_POST['evaluator_name']) ? trim($_POST['evaluator_name']) : "";
$ratings = isset($_POST['rate']) && is_array($_POST['rate']) ? $_POST['rate'] : [];

if ($activity_id == 0) {
    die("Invalid Activity.");
}

// Only an approved activity may be evaluated, the same rule the form uses
$activity = $conn->query("SELECT * FROM activities WHERE id = $activity_id AND status = 'approved' LIMIT 1")->fetch_assoc();

if (!$activity) {
    die("This activity is not open for evaluation.");
}

if (empty($ratings)) {
    die("Please answer all questions.");
}

// If blank, save as Anonymous
if ($evaluator_name == "") {
    $evaluator_name = "Anonymous";
}

$evaluator_name = mb_substr($evaluator_name, 0, 100);


/* =========================================================
   SAVE THE OLD WAY
   (the questionnaire tables do not exist yet)
========================================================= */

$escaped_name = $conn->real_escape_string($evaluator_name);

$result = $conn->query("SELECT IFNULL(MAX(evaluation_id),0)+1 AS next_id FROM evaluation_answers");
$evaluation_id = (int)$result->fetch_assoc()['next_id'];

foreach ($ratings as $question_id => $rate) {

    $question_id = intval($question_id);
    $rate = intval($rate);

    if ($rate < 1 || $rate > 5) {
        continue;
    }

    $conn->query("INSERT INTO evaluation_answers
        (evaluation_id, question_id, rate, activity_id, evaluator_name, submitted_at)
        VALUES ($evaluation_id, $question_id, $rate, $activity_id, '$escaped_name', NOW())");
}

echo "<script>
alert('Evaluation submitted successfully!');
window.location='evaluate.php?activity_id=".$activity_id."';
</script>";
exit;
