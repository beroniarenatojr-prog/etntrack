<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die("Invalid request.");
}

$activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
$evaluator_name = isset($_POST['evaluator_name']) ? trim($_POST['evaluator_name']) : "Anonymous";
$ratings = isset($_POST['rate']) ? $_POST['rate'] : [];

if ($activity_id == 0) {
    die("Invalid Activity.");
}

if (empty($ratings)) {
    die("Please answer all questions.");
}

// If blank, save as Anonymous
if ($evaluator_name == "") {
    $evaluator_name = "Anonymous";
}

// Prevent SQL errors
$evaluator_name = $conn->real_escape_string($evaluator_name);

// Generate one evaluation_id for this evaluation
$result = $conn->query("SELECT IFNULL(MAX(evaluation_id),0)+1 AS next_id FROM evaluation_answers");
$row = $result->fetch_assoc();
$evaluation_id = $row['next_id'];

// Save all answers
foreach ($ratings as $question_id => $rate) {

    $question_id = intval($question_id);
    $rate = intval($rate);

    $sql = "INSERT INTO evaluation_answers
            (evaluation_id, question_id, rate, activity_id, evaluator_name)
            VALUES
            ('$evaluation_id', '$question_id', '$rate', '$activity_id', '$evaluator_name')";

    if (!$conn->query($sql)) {
        die("Database Error: " . $conn->error);
    }
}

echo "<script>
alert('Evaluation submitted successfully!');
window.location='evaluate.php?activity_id=".$activity_id."';
</script>";
?>