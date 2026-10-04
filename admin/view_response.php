<?php

/*
|--------------------------------------------------------------------------
| ONE RESPONSE
|--------------------------------------------------------------------------
| Everything one respondent answered on the QR evaluation form, question by
| question, with who they are and their CCS rating.
|
|   index.php?page=view_response&id=N
*/

require_once 'questionnaire_lib.php';

$e = function($text){ return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); };
$version = @filemtime('assets/css/questionnaire.css') ?: 1;

$id = (int)($_GET['id'] ?? 0);
$response = null;

if (questionnaires_ready($conn) && $id > 0) {
    $with_comments = $conn->query("SHOW COLUMNS FROM evaluation_responses LIKE 'comments'")->num_rows > 0;
    $response = $conn->query("
        SELECT r.*, ".($with_comments ? "" : "NULL AS comments, ")."a.activity_name, a.activity_date, a.venue
        FROM evaluation_responses r
        LEFT JOIN activities a ON a.id = r.activity_id
        WHERE r.id = $id
    ")->fetch_assoc();
}

$questionnaire = $response && $response['questionnaire_id']
    ? $conn->query("SELECT * FROM questionnaires WHERE id = ".(int)$response['questionnaire_id'])->fetch_assoc()
    : null;

?>

<link rel="stylesheet" href="assets/css/questionnaire.css?v=<?php echo $version; ?>">

<div class="qb-wrap er-wrap">

<?php if (!$response || !$questionnaire): ?>

    <div class="qb-banner qb-banner-warning">
        <i class="fas fa-search"></i>
        <div>
            <b>Response not found.</b>
            <a href="index.php?page=evaluation_results">Back to Evaluation Results</a>
        </div>
    </div>

<?php else:

    $structure = questionnaire_structure($conn, (int)$questionnaire['id']);
    $scale = questionnaire_scale($questionnaire);
    $scale_max = max(array_map(function($step){ return (int)$step['value']; }, $scale));
    $scale_labels = array();
    foreach ($scale as $step) {
        $scale_labels[(int)$step['value']] = $step['label'];
    }

    // What this respondent answered, by question
    $answers = array();
    $result = $conn->query("SELECT question_id, rate, option_id, answer_text FROM evaluation_answers WHERE evaluation_id = $id ORDER BY id");
    while ($row = $result->fetch_assoc()) {
        $answers[(int)$row['question_id']][] = $row;
    }

    $likert_sum = 0;
    $likert_count = 0;
    foreach ($structure['sections'] as $section) {
        foreach ($section['questions'] as $question) {
            if ($question['question_type'] !== 'likert') {
                continue;
            }
            foreach ($answers[$question['id']] ?? array() as $answer) {
                if ($answer['rate'] !== null) {
                    $likert_sum += (int)$answer['rate'];
                    $likert_count++;
                }
            }
        }
    }

    $ccs = $likert_count ? $likert_sum / ($likert_count * $scale_max) * 100 : null;
    $types = questionnaire_respondent_types();
    $back = 'index.php?page=evaluation_results&questionnaire='.(int)$questionnaire['id'];
?>

<div class="qb-head">
    <div class="qb-head-icon"><i class="fas fa-user-check"></i></div>
    <div class="qb-head-text">
        <a href="<?php echo $back; ?>" class="qb-back no-print"><i class="fas fa-arrow-left"></i> Evaluation Results</a>
        <h1><?php echo $e($response['respondent_name'] ?: 'Anonymous'); ?></h1>
        <p><?php echo $e($questionnaire['title']); ?></p>
    </div>
    <button type="button" class="qb-btn qb-btn-light no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="row">

    <div class="col-lg-4">
        <div class="qb-card">
            <div class="qb-card-head"><div><h3>Respondent</h3></div></div>
            <dl class="qb-summary er-summary-narrow">
                <dt>Name</dt><dd><?php echo $e($response['respondent_name'] ?: 'Anonymous'); ?></dd>
                <dt>Barangay</dt><dd><?php echo $response['barangay'] ? $e($response['barangay']) : '<span class="text-muted">Not given</span>'; ?></dd>
                <dt>Respondent</dt><dd><?php echo isset($types[$response['respondent_type']]) ? $e($types[$response['respondent_type']]) : '<span class="text-muted">Not given</span>'; ?></dd>
                <dt>Activity</dt><dd><?php echo $e($response['activity_name']); ?></dd>
                <?php if ($response['activity_date']): ?>
                    <dt>Activity Date</dt><dd><?php echo $e(date('F j, Y', strtotime($response['activity_date']))); ?></dd>
                <?php endif; ?>
                <dt>Submitted</dt><dd><?php echo $response['submitted_at'] ? $e(date('F j, Y g:i A', strtotime($response['submitted_at']))) : ''; ?></dd>
                <dt>Total Score</dt><dd><?php echo $likert_count ? $likert_sum.' of '.($likert_count * $scale_max) : '&ndash;'; ?></dd>
                <dt>CCS Rating</dt><dd><b><?php echo $ccs !== null ? number_format($ccs, 2).'%' : '&ndash;'; ?></b></dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="qb-card">
            <div class="qb-card-head"><div><h3>Answers</h3></div></div>

            <?php $number = 0; foreach ($structure['sections'] as $section): ?>

                <h4 class="er-section-title"><?php echo $e($section['title']); ?></h4>

                <ol class="er-answers" start="<?php echo $number + 1; ?>">
                <?php foreach ($section['questions'] as $question):
                    $number++;
                    $given = $answers[$question['id']] ?? array();
                    $options = array();
                    foreach ($question['options'] as $option) {
                        $options[$option['id']] = $option['label'];
                    }
                ?>
                    <li>
                        <div class="er-answer-question"><?php echo $e($question['question']); ?></div>
                        <div class="er-answer">
                        <?php if (!$given): ?>
                            <span class="text-muted">Not answered</span>
                        <?php else: foreach ($given as $answer): ?>
                            <?php if ($question['question_type'] === 'likert'): ?>
                                <span class="er-answer-chip"><b><?php echo (int)$answer['rate']; ?></b> <?php echo $e($scale_labels[(int)$answer['rate']] ?? ''); ?></span>
                            <?php elseif ($question['question_type'] === 'rating'): ?>
                                <span class="er-answer-chip"><b><?php echo (int)$answer['rate']; ?></b> out of <?php echo (int)(((array)$question['settings'])['max'] ?? 5); ?></span>
                            <?php elseif ($answer['option_id'] !== null): ?>
                                <span class="er-answer-chip"><?php echo $e($options[(int)$answer['option_id']] ?? 'An answer that was later removed'); ?></span>
                            <?php else: ?>
                                <div class="er-answer-text"><?php echo nl2br($e($answer['answer_text'])); ?></div>
                            <?php endif; ?>
                        <?php endforeach; endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ol>

            <?php endforeach; ?>

            <h4 class="er-section-title">Other comments/suggestions</h4>
            <p class="er-answer-text"><?php echo $response['comments'] ? nl2br($e($response['comments'])) : '<span class="text-muted">None</span>'; ?></p>
        </div>
    </div>

</div>

<?php endif; ?>

</div>
