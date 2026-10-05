<?php

/*
|--------------------------------------------------------------------------
| EVALUATION RESULTS
|--------------------------------------------------------------------------
| The results of one questionnaire: how many responded, the overall and
| section scores, the CCS rating, how every question was answered, the
| comments and suggestions, and who answered. Can be narrowed to one
| activity.
|
|   index.php?page=evaluation_results&questionnaire=N[&activity=N]
|
| Scores use the Likert (agree-disagree) answers only. CCS rating is the
| ISU formula: total score / highest possible score x 100, which for the
| official form (10 questions, highest answer 5) is total score / 50 x 100.
| It is worked out for each respondent and then averaged.
|
| Until the database update that adds questionnaires has been applied, the
| old results page is used (evaluation_results_legacy.php).
*/

require_once 'questionnaire_lib.php';

if (!questionnaires_ready($conn)) {
    include 'admin/evaluation_results_legacy.php';
    return;
}

$e = function($text){ return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); };
$pct = function($part, $whole){ return $whole > 0 ? $part / $whole * 100 : 0; };
$version = @filemtime('assets/css/questionnaire.css') ?: 1;


/* =========================================================
   WHICH QUESTIONNAIRE, WHICH ACTIVITY
========================================================= */

$choices = array();

$result = $conn->query("
    SELECT q.id, q.title, q.academic_year, q.semester, q.status,
        (SELECT COUNT(*) FROM evaluation_responses r WHERE r.questionnaire_id = q.id) AS responses
    FROM questionnaires q
    WHERE q.status IN ('active', 'closed', 'archived')
       OR EXISTS (SELECT 1 FROM evaluation_responses r WHERE r.questionnaire_id = q.id)
    ORDER BY FIELD(q.status, 'active', 'closed', 'archived', 'ready', 'draft'), q.published_at DESC, q.id DESC
");

while ($row = $result->fetch_assoc()) {
    $choices[(int)$row['id']] = $row;
}

$questionnaire_id = (int)($_GET['questionnaire'] ?? 0);

// Opened for one activity only (from Activities or a project): the questionnaire it was answered with
if (!isset($choices[$questionnaire_id]) && (int)($_GET['activity'] ?? 0) > 0) {
    $answered = $conn->query("
        SELECT questionnaire_id, COUNT(*) AS total
        FROM evaluation_responses
        WHERE activity_id = ".(int)$_GET['activity']."
        GROUP BY questionnaire_id
        ORDER BY total DESC, questionnaire_id DESC
        LIMIT 1
    ")->fetch_assoc();
    if ($answered) {
        $questionnaire_id = (int)$answered['questionnaire_id'];
    }
}

if (!isset($choices[$questionnaire_id])) {
    $questionnaire_id = $choices ? (int)array_key_first($choices) : 0;
}

$questionnaire = $questionnaire_id
    ? $conn->query("SELECT * FROM questionnaires WHERE id = $questionnaire_id")->fetch_assoc()
    : null;

$activity_id = (int)($_GET['activity'] ?? 0);
$activity_choices = array();

if ($questionnaire) {
    $result = $conn->query("
        SELECT a.id, a.activity_name, a.activity_date, COUNT(r.id) AS responses
        FROM evaluation_responses r
        INNER JOIN activities a ON a.id = r.activity_id
        WHERE r.questionnaire_id = $questionnaire_id
        GROUP BY a.id, a.activity_name, a.activity_date
        ORDER BY a.activity_date DESC, a.id DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $activity_choices[(int)$row['id']] = $row;
    }
}

if (!isset($activity_choices[$activity_id])) {
    $activity_id = 0;
}


/* =========================================================
   ADDING UP THE ANSWERS
========================================================= */

$responses = array();
$questions = array();       // question id => question, with its section
$stats = array();           // question id => what was answered
$section_scores = array();  // section id => Likert total and count
$activity_scores = array(); // activity id => responses, Likert total and count, CCS
$overall = array('sum' => 0, 'count' => 0, 'ccs_total' => 0, 'ccs_count' => 0);
$comments = array();

if ($questionnaire) {

    $structure = questionnaire_structure($conn, $questionnaire_id);

    $scale = questionnaire_scale($questionnaire);
    usort($scale, function($a, $b){ return $a['value'] <=> $b['value']; });
    $scale_max = max(array_map(function($step){ return (int)$step['value']; }, $scale));
    $scale_min = min(array_map(function($step){ return (int)$step['value']; }, $scale));

    // A colour from 1 (lowest answer, red) to 5 (highest, green), whatever the scale length
    $tone = function($value) use ($scale_min, $scale_max){
        return $scale_max > $scale_min ? (int)round(($value - $scale_min) / ($scale_max - $scale_min) * 4) + 1 : 5;
    };

    foreach ($structure['sections'] as $section) {
        foreach ($section['questions'] as $question) {
            $question['section_id'] = $section['id'];
            $questions[$question['id']] = $question;
            $stats[$question['id']] = array(
                'respondents' => array(),
                'sum' => 0,
                'count' => 0,
                'values' => array(),
                'options' => array(),
                'texts' => array()
            );
        }
    }

    $where = "r.questionnaire_id = $questionnaire_id".($activity_id ? " AND r.activity_id = $activity_id" : "");
    $with_comments = $conn->query("SHOW COLUMNS FROM evaluation_responses LIKE 'comments'")->num_rows > 0;

    $result = $conn->query("
        SELECT r.id, r.activity_id, r.respondent_name, r.respondent_type, r.barangay, r.submitted_at,
            ".($with_comments ? "r.comments" : "NULL AS comments").",
            a.activity_name
        FROM evaluation_responses r
        LEFT JOIN activities a ON a.id = r.activity_id
        WHERE $where
        ORDER BY r.submitted_at DESC, r.id DESC
    ");

    while ($row = $result->fetch_assoc()) {
        $row['likert_sum'] = 0;
        $row['likert_count'] = 0;
        $responses[(int)$row['id']] = $row;
    }

    $result = $conn->query("
        SELECT ea.evaluation_id, ea.question_id, ea.rate, ea.option_id, ea.answer_text
        FROM evaluation_answers ea
        INNER JOIN evaluation_responses r ON r.id = ea.evaluation_id
        WHERE $where
        ORDER BY ea.evaluation_id, ea.id
    ");

    while ($row = $result->fetch_assoc()) {

        $response_id = (int)$row['evaluation_id'];
        $question_id = (int)$row['question_id'];

        if (!isset($questions[$question_id], $responses[$response_id])) {
            continue;
        }

        $question = $questions[$question_id];
        $stat = &$stats[$question_id];
        $stat['respondents'][$response_id] = true;

        switch ($question['question_type']) {

            case 'likert':
                if ($row['rate'] === null) {
                    break;
                }
                $rate = (int)$row['rate'];
                $stat['sum'] += $rate;
                $stat['count']++;
                $stat['values'][$rate] = ($stat['values'][$rate] ?? 0) + 1;
                $responses[$response_id]['likert_sum'] += $rate;
                $responses[$response_id]['likert_count']++;
                $section_id = $question['section_id'];
                $section_scores[$section_id]['sum'] = ($section_scores[$section_id]['sum'] ?? 0) + $rate;
                $section_scores[$section_id]['count'] = ($section_scores[$section_id]['count'] ?? 0) + 1;
                break;

            case 'rating':
                if ($row['rate'] === null) {
                    break;
                }
                $rate = (int)$row['rate'];
                $stat['sum'] += $rate;
                $stat['count']++;
                $stat['values'][$rate] = ($stat['values'][$rate] ?? 0) + 1;
                break;

            case 'single_choice':
            case 'multiple_choice':
            case 'yes_no':
                if ($row['option_id'] !== null) {
                    $option = (int)$row['option_id'];
                    $stat['options'][$option] = ($stat['options'][$option] ?? 0) + 1;
                    $stat['count']++;
                }
                break;

            default:
                if (trim((string)$row['answer_text']) !== '') {
                    $stat['texts'][] = array('response' => $response_id, 'text' => $row['answer_text']);
                    $stat['count']++;
                }
        }

        unset($stat);
    }

    // Each respondent's CCS rating, then the averages
    foreach ($responses as $id => &$response) {

        $response['ccs'] = null;

        if ($response['likert_count'] > 0) {
            $response['ccs'] = $response['likert_sum'] / ($response['likert_count'] * $scale_max) * 100;
            $overall['ccs_total'] += $response['ccs'];
            $overall['ccs_count']++;
        }

        $overall['sum'] += $response['likert_sum'];
        $overall['count'] += $response['likert_count'];

        $activity = (int)$response['activity_id'];
        if (!isset($activity_scores[$activity])) {
            $activity_scores[$activity] = array('name' => $response['activity_name'], 'responses' => 0, 'sum' => 0, 'count' => 0, 'ccs_total' => 0, 'ccs_count' => 0);
        }
        $activity_scores[$activity]['responses']++;
        $activity_scores[$activity]['sum'] += $response['likert_sum'];
        $activity_scores[$activity]['count'] += $response['likert_count'];
        if ($response['ccs'] !== null) {
            $activity_scores[$activity]['ccs_total'] += $response['ccs'];
            $activity_scores[$activity]['ccs_count']++;
        }

        if (trim((string)$response['comments']) !== '') {
            $comments[] = $response;
        }
    }
    unset($response);
}

$mean = $overall['count'] ? $overall['sum'] / $overall['count'] : null;
$ccs = $overall['ccs_count'] ? $overall['ccs_total'] / $overall['ccs_count'] : null;
$respondent_types = questionnaire_respondent_types();
$status_labels = questionnaire_status_labels();

$filter_link = function($activity = 0) use ($questionnaire_id){
    return 'index.php?page=evaluation_results&questionnaire='.$questionnaire_id.($activity ? '&activity='.$activity : '');
};

?>

<link rel="stylesheet" href="assets/css/questionnaire.css?v=<?php echo $version; ?>">

<div class="qb-wrap er-wrap">


<!-- =====================================================
     HEADER AND FILTERS
===================================================== -->

<div class="qb-head">
    <div class="qb-head-icon"><i class="fas fa-chart-bar"></i></div>
    <div class="qb-head-text">
        <h1>Evaluation Results</h1>
        <p>Scores, answers and comments from the QR evaluation forms.</p>
    </div>
    <?php if ($questionnaire): ?>
        <button type="button" class="qb-btn qb-btn-light er-print no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    <?php endif; ?>
</div>

<?php if (!$choices): ?>

    <div class="qb-card">
        <div class="qb-empty-state">
            <i class="fas fa-chart-bar"></i>
            <p>No results yet.</p>
            <small>Results appear here once a questionnaire is published and respondents answer it.</small>
            <a href="index.php?page=questionnaire" class="qb-btn qb-btn-primary">Go to Questionnaires</a>
        </div>
    </div>

<?php else: ?>

<form class="qb-card er-filters no-print" method="get" action="index.php">
    <input type="hidden" name="page" value="evaluation_results">

    <div class="er-filter">
        <label for="er-questionnaire">Questionnaire</label>
        <select id="er-questionnaire" name="questionnaire" class="form-control" onchange="this.form.activity.value = ''; this.form.submit()">
            <?php foreach ($choices as $id => $choice): ?>
                <option value="<?php echo $id; ?>"<?php echo $id === $questionnaire_id ? ' selected' : ''; ?>>
                    <?php echo $e($choice['title']); ?>
                    (<?php echo $e(trim($choice['academic_year'].' '.$choice['semester']) ?: 'no term'); ?>,
                    <?php echo $e($status_labels[$choice['status']] ?? $choice['status']); ?>,
                    <?php echo (int)$choice['responses']; ?> <?php echo (int)$choice['responses'] === 1 ? 'response' : 'responses'; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="er-filter">
        <label for="er-activity">Activity</label>
        <select id="er-activity" name="activity" class="form-control" onchange="this.form.submit()">
            <option value="">All activities</option>
            <?php foreach ($activity_choices as $id => $choice): ?>
                <option value="<?php echo $id; ?>"<?php echo $id === $activity_id ? ' selected' : ''; ?>>
                    <?php echo $e($choice['activity_name']); ?>
                    (<?php echo (int)$choice['responses']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <noscript><button type="submit" class="qb-btn qb-btn-primary">Show</button></noscript>
</form>

<div class="er-title">
    <h2><?php echo $e($questionnaire['title']); ?></h2>
    <p>
        <?php echo $e(trim($questionnaire['academic_year'].' '.$questionnaire['semester']) ?: 'Academic year not set'); ?>
        &middot; <?php echo $e(questionnaire_evaluation_types()[$questionnaire['evaluation_type']] ?? ''); ?>
        &middot; <span class="qb-status qb-status-<?php echo $e($questionnaire['status']); ?> er-status"><?php echo $e($status_labels[$questionnaire['status']] ?? ''); ?></span>
        <?php if ($activity_id): ?>
            <br>Showing only: <b><?php echo $e($activity_choices[$activity_id]['activity_name']); ?></b>
            &middot; <a href="<?php echo $filter_link(); ?>" class="no-print">Show all activities</a>
        <?php endif; ?>
    </p>
</div>


<!-- =====================================================
     SUMMARY
===================================================== -->

<div class="er-cards">

    <div class="er-card">
        <span class="er-card-icon"><i class="fas fa-users"></i></span>
        <div>
            <span class="er-card-label">Respondents</span>
            <strong><?php echo number_format(count($responses)); ?></strong>
        </div>
    </div>

    <div class="er-card">
        <span class="er-card-icon"><i class="fas fa-star-half-alt"></i></span>
        <div>
            <span class="er-card-label">Overall Mean</span>
            <strong><?php echo $mean !== null ? number_format($mean, 2) : '&ndash;'; ?></strong>
            <small><?php echo $mean !== null ? 'out of '.$scale_max.' &middot; '.questionnaire_rating_label($mean, $scale_max) : 'no ratings yet'; ?></small>
        </div>
    </div>

    <div class="er-card">
        <span class="er-card-icon"><i class="fas fa-percent"></i></span>
        <div>
            <span class="er-card-label">CCS Rating</span>
            <strong><?php echo $ccs !== null ? number_format($ccs, 2).'%' : '&ndash;'; ?></strong>
            <small title="Total score &divide; highest possible score &times; 100">of the highest possible score</small>
        </div>
    </div>

    <div class="er-card">
        <span class="er-card-icon"><i class="fas fa-calendar-check"></i></span>
        <div>
            <span class="er-card-label"><?php echo $activity_id ? 'Activity Date' : 'Activities Evaluated'; ?></span>
            <?php if ($activity_id): ?>
                <strong class="er-card-text">
                    <?php echo $activity_choices[$activity_id]['activity_date'] ? $e(date('M j, Y', strtotime($activity_choices[$activity_id]['activity_date']))) : '&ndash;'; ?>
                </strong>
            <?php else: ?>
                <strong><?php echo number_format(count($activity_choices)); ?></strong>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!$responses): ?>

    <div class="qb-card">
        <div class="qb-empty-state">
            <i class="fas fa-inbox"></i>
            <p>No responses yet.</p>
            <small>
                <?php echo $questionnaire['status'] === 'active'
                    ? 'Responses appear here as soon as respondents submit the QR evaluation form.'
                    : 'This questionnaire did not receive any responses.'; ?>
            </small>
        </div>
    </div>

<?php else: ?>


<!-- =====================================================
     SCORES BY SECTION
===================================================== -->

<?php
$scored_sections = array_filter($structure['sections'], function($section) use ($section_scores){
    return isset($section_scores[$section['id']]);
});
?>

<?php if ($scored_sections): ?>
<div class="qb-card">
    <div class="qb-card-head">
        <div>
            <h3>Scores by Section</h3>
            <p>The mean of the Likert answers in each section, out of <?php echo $scale_max; ?>.</p>
        </div>
    </div>

    <div class="table-responsive">
    <table class="er-table">
        <thead>
            <tr>
                <th>Section</th>
                <th class="er-num">Mean</th>
                <th class="er-bar-col">Score</th>
                <th>Interpretation</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($scored_sections as $section):
            $score = $section_scores[$section['id']];
            $section_mean = $score['sum'] / $score['count'];
        ?>
            <tr>
                <td><?php echo $e($section['title']); ?></td>
                <td class="er-num"><b><?php echo number_format($section_mean, 2); ?></b></td>
                <td class="er-bar-col">
                    <div class="er-bar" role="img" aria-label="<?php echo number_format($section_mean, 2); ?> out of <?php echo $scale_max; ?>">
                        <span style="width: <?php echo round($pct($section_mean, $scale_max), 1); ?>%"></span>
                    </div>
                </td>
                <td><span class="er-label"><?php echo questionnaire_rating_label($section_mean, $scale_max); ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Overall</th>
                <th class="er-num"><?php echo number_format($mean, 2); ?></th>
                <th class="er-bar-col">
                    <div class="er-bar er-bar-strong"><span style="width: <?php echo round($pct($mean, $scale_max), 1); ?>%"></span></div>
                </th>
                <th><span class="er-label"><?php echo questionnaire_rating_label($mean, $scale_max); ?></span></th>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<?php endif; ?>


<!-- =====================================================
     EVERY QUESTION
===================================================== -->

<div class="qb-card">
    <div class="qb-card-head">
        <div>
            <h3>Question Breakdown</h3>
            <p>How each question was answered.</p>
        </div>
    </div>

    <?php $number = 0; foreach ($structure['sections'] as $section): ?>

        <h4 class="er-section-title"><?php echo $e($section['title']); ?></h4>

        <?php foreach ($section['questions'] as $question):
            $number++;
            $stat = $stats[$question['id']];
            $answered = count($stat['respondents']);
            $type = $question['question_type'];
        ?>

        <div class="er-question">

            <div class="er-question-head">
                <span class="qb-qno"><?php echo $number; ?></span>
                <div class="er-question-text">
                    <?php echo $e($question['question']); ?>
                    <div class="er-question-meta">
                        <?php echo $e(questionnaire_types()[$type] ?? $type); ?>
                        &middot; <?php echo $answered; ?> of <?php echo count($responses); ?> answered
                        <?php if (!$question['is_active']): ?>&middot; no longer asked<?php endif; ?>
                    </div>
                </div>
                <?php if (($type === 'likert' || $type === 'rating') && $stat['count']):
                    $question_mean = $stat['sum'] / $stat['count'];
                    $out_of = $type === 'likert' ? $scale_max : (int)(((array)$question['settings'])['max'] ?? 5);
                ?>
                    <div class="er-question-score">
                        <b><?php echo number_format($question_mean, 2); ?></b>
                        <small>
                            out of <?php echo $out_of; ?>
                            <?php if ($type === 'likert'): ?>&middot; <?php echo questionnaire_rating_label($question_mean, $scale_max); ?><?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$answered): ?>

                <p class="er-none">No answers yet.</p>

            <?php elseif ($type === 'likert'): ?>

                <div class="er-dist" role="img" aria-label="How the answers were spread over the scale">
                    <?php foreach (array_reverse($scale) as $step):
                        $count = $stat['values'][(int)$step['value']] ?? 0;
                        if (!$count) continue;
                    ?>
                        <span class="er-tone-<?php echo $tone((int)$step['value']); ?>" style="width: <?php echo round($pct($count, $stat['count']), 2); ?>%"
                              title="<?php echo $e($step['label']); ?>: <?php echo $count; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <ul class="er-options">
                    <?php foreach (array_reverse($scale) as $step):
                        $count = $stat['values'][(int)$step['value']] ?? 0;
                    ?>
                        <li>
                            <span class="er-swatch er-tone-<?php echo $tone((int)$step['value']); ?>"></span>
                            <span class="er-option-label"><b><?php echo (int)$step['value']; ?></b> <?php echo $e($step['label']); ?></span>
                            <span class="er-option-count"><?php echo $count; ?> <small>(<?php echo number_format($pct($count, $stat['count']), 1); ?>%)</small></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <?php elseif ($type === 'rating'):
                $settings = (array)$question['settings'];
                $min = isset($settings['min']) ? (int)$settings['min'] : 1;
                $max = isset($settings['max']) ? (int)$settings['max'] : 5;
                $most = max(1, max($stat['values'] ?: array(0)));
            ?>

                <div class="er-columns" role="img" aria-label="How many chose each number">
                    <?php for ($n = $min; $n <= $max; $n++):
                        $count = $stat['values'][$n] ?? 0;
                    ?>
                        <div class="er-column" title="<?php echo $n; ?>: <?php echo $count; ?>">
                            <span class="er-column-count"><?php echo $count ?: ''; ?></span>
                            <span class="er-column-bar" style="height: <?php echo round($pct($count, $most), 1); ?>%"></span>
                            <span class="er-column-label"><?php echo $n; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php elseif ($type === 'single_choice' || $type === 'multiple_choice' || $type === 'yes_no'): ?>

                <ul class="er-options er-options-bars">
                    <?php foreach ($question['options'] as $option):
                        $count = $stat['options'][$option['id']] ?? 0;
                        $share = $pct($count, $answered);
                    ?>
                        <li>
                            <span class="er-option-label"><?php echo $e($option['label']); ?></span>
                            <span class="er-bar"><span style="width: <?php echo round($share, 1); ?>%"></span></span>
                            <span class="er-option-count"><?php echo $count; ?> <small>(<?php echo number_format($share, 1); ?>%)</small></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($type === 'multiple_choice'): ?>
                    <p class="er-note">Respondents could tick more than one, so the shares can add up to more than 100%.</p>
                <?php endif; ?>

            <?php else:
                $texts = array_reverse($stat['texts']);
            ?>

                <ul class="er-texts">
                    <?php foreach (array_slice($texts, 0, 5) as $text): ?>
                        <li><?php echo nl2br($e($text['text'])); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($texts) > 5): ?>
                    <details class="er-more">
                        <summary>Show all <?php echo count($texts); ?> answers</summary>
                        <ul class="er-texts">
                            <?php foreach (array_slice($texts, 5) as $text): ?>
                                <li><?php echo nl2br($e($text['text'])); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>

            <?php endif; ?>

        </div>

        <?php endforeach; ?>

    <?php endforeach; ?>
</div>


<!-- =====================================================
     COMMENTS AND SUGGESTIONS
===================================================== -->

<div class="qb-card">
    <div class="qb-card-head">
        <div>
            <h3>Comments and Suggestions</h3>
            <p><?php echo count($comments); ?> of <?php echo count($responses); ?> respondents wrote a comment.</p>
        </div>
    </div>

    <?php if (!$comments): ?>
        <p class="er-none">No comments yet.</p>
    <?php else: ?>
        <ul class="er-comments">
            <?php foreach ($comments as $comment): ?>
                <li>
                    <p><?php echo nl2br($e($comment['comments'])); ?></p>
                    <small>
                        <?php echo $e($comment['respondent_name'] ?: 'Anonymous'); ?>
                        <?php if ($comment['barangay']): ?>&middot; Brgy. <?php echo $e($comment['barangay']); ?><?php endif; ?>
                        &middot; <?php echo $e($comment['activity_name']); ?>
                        <?php if ($comment['submitted_at']): ?>&middot; <?php echo $e(date('M j, Y', strtotime($comment['submitted_at']))); ?><?php endif; ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>


<!-- =====================================================
     BY ACTIVITY
===================================================== -->

<?php if (!$activity_id && count($activity_scores) > 0): ?>
<div class="qb-card">
    <div class="qb-card-head">
        <div>
            <h3>Results by Activity</h3>
            <p>Choose an activity to see only its results.</p>
        </div>
    </div>

    <div class="table-responsive">
    <table class="er-table">
        <thead>
            <tr>
                <th>Activity</th>
                <th class="er-num">Respondents</th>
                <th class="er-num">Mean</th>
                <th class="er-num">CCS Rating</th>
                <th>Interpretation</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($activity_scores as $id => $score):
            $activity_mean = $score['count'] ? $score['sum'] / $score['count'] : null;
        ?>
            <tr>
                <td><a href="<?php echo $filter_link($id); ?>"><?php echo $e($score['name'] ?: 'Activity #'.$id); ?></a></td>
                <td class="er-num"><?php echo $score['responses']; ?></td>
                <td class="er-num"><?php echo $activity_mean !== null ? number_format($activity_mean, 2) : '&ndash;'; ?></td>
                <td class="er-num"><?php echo $score['ccs_count'] ? number_format($score['ccs_total'] / $score['ccs_count'], 2).'%' : '&ndash;'; ?></td>
                <td><?php echo $activity_mean !== null ? '<span class="er-label">'.questionnaire_rating_label($activity_mean, $scale_max).'</span>' : ''; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>


<!-- =====================================================
     RESPONDENTS
===================================================== -->

<div class="qb-card">
    <div class="qb-card-head">
        <div>
            <h3>Respondents</h3>
            <p>Every response, newest first. Open one to see all of its answers.</p>
        </div>
    </div>

    <div class="table-responsive">
    <table class="er-table" id="er-respondents">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Barangay</th>
                <th>Respondent</th>
                <th>Activity</th>
                <th>Submitted</th>
                <th class="er-num">CCS Rating</th>
                <th class="no-print"></th>
            </tr>
        </thead>
        <tbody>
        <?php $row_number = 0; foreach ($responses as $id => $response): $row_number++; ?>
            <tr>
                <td><?php echo $row_number; ?></td>
                <td><?php echo $e($response['respondent_name'] ?: 'Anonymous'); ?></td>
                <td><?php echo $e($response['barangay']); ?></td>
                <td><?php echo $e($respondent_types[$response['respondent_type']] ?? ''); ?></td>
                <td><?php echo $e($response['activity_name']); ?></td>
                <td data-order="<?php echo $e($response['submitted_at']); ?>">
                    <?php echo $response['submitted_at'] ? $e(date('M j, Y g:i A', strtotime($response['submitted_at']))) : ''; ?>
                </td>
                <td class="er-num"><?php echo $response['ccs'] !== null ? number_format($response['ccs'], 2).'%' : '&ndash;'; ?></td>
                <td class="no-print">
                    <a href="index.php?page=view_response&id=<?php echo $id; ?>" class="qb-btn qb-btn-small">
                        <i class="fas fa-eye"></i> View
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php endif; ?>

<?php endif; ?>

</div>

<script>
$(function(){
    // Search and pages for a long list of respondents
    if ($('#er-respondents tbody tr').length > 10 && $.fn.DataTable) {
        $('#er-respondents').DataTable({
            order: [[5, 'desc']],
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: [7] }],
            language: { search: 'Search:', emptyTable: 'No responses yet.' }
        });
    }
});
</script>
